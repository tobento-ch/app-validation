<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\App\Validation\Test\Http;

use PHPUnit\Framework\TestCase;
use Tobento\App\AppInterface;
use Tobento\App\AppFactory;
use Tobento\App\Validation\Http\ValidationRequest;
use Tobento\App\Validation\Boot\Validator;
use Tobento\App\Validation\Exception\ValidationException;
use Tobento\App\Http\Boot\Http;
use Tobento\App\Http\ResponseEmitterInterface;
use Tobento\App\Http\Test\Mock\ResponseEmitter;
use Tobento\App\Http\Test\TestResponse;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Filesystem\Dir;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ServerRequestInterface;

/**
 * AppValidationRequestTest
 */
class AppValidationRequestTest extends TestCase
{
    protected function createApp(bool $deleteDir = true): AppInterface
    {
        if ($deleteDir) {
            (new Dir())->delete(__DIR__.'/../app/');
        }
        
        (new Dir())->create(__DIR__.'/../app/');
        (new Dir())->create(__DIR__.'/../app/config/');
        
        $app = (new AppFactory())->createApp();
        
        $app->dirs()
            ->dir(realpath(__DIR__.'/../app/'), 'app')
            ->dir($app->dir('app').'config', 'config', group: 'config');
        
        // Replace response emitter for testing:
        $app->on(ResponseEmitterInterface::class, ResponseEmitter::class);
        
        // minimum requirements
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\Http\Boot\RequesterResponser::class);
        $app->boot(Validator::class);
        
        return $app;
    }
    
    public static function tearDownAfterClass(): void
    {
        (new Dir())->delete(__DIR__.'/../app/');
    }
    
    public function testValidationWithoutErrorHandlerThrowsException()
    {
        $this->expectExceptionMessage('Validation failed');
        
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'POST',
                uri: 'foo',
                serverParams: [],
            )->withParsedBody(['']);
        });
        
        $app->booting();
        
        $app->route('POST', 'foo', function(ValidationRequest $req) {
            
            $validation = $req->validate(
                rules: [
                    'title' => 'required|alpha',
                ],
                errorMessage: 'Validation failed',
            );
            
            return $req->responser()->json(data: [], code: 201);
        });
        
        $app->run();
    }
    
    public function testInterfacesAreAvailbale()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'POST',
                uri: 'foo',
                serverParams: [],
            )->withParsedBody(['title' => 'lorem']);
        });
        
        $app->boot(\Tobento\App\Validation\Boot\HttpValidationErrorHandler::class);
        $app->booting();
        
        $app->route('POST', 'foo', function(ValidationRequest $req) {
            
            $validation = $req->validate(
                rules: [
                    'title' => 'required|alpha',
                ],
            );
            
            return $req->responser()->json(data: [
                'requester' => $req->requester() instanceof RequesterInterface,
                'responser' => $req->responser() instanceof ResponserInterface,
                'router' => $req->router() instanceof RouterInterface,
            ], code: 200);
        });
        
        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(200)
            ->isBodySame('{"requester":true,"responser":true,"router":true}');
    }
    
    public function testValidationFailsReturnsRedirectResponse()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'POST',
                uri: 'foo',
                serverParams: [],
            );
        });
        
        $app->boot(\Tobento\App\Validation\Boot\HttpValidationErrorHandler::class);
        $app->booting();
        
        $app->route('POST', 'foo', function(ValidationRequest $req) {
            
            $validation = $req->validate(
                rules: [
                    'title' => 'required|alpha',
                ],
            );
            
            return $req->responser()->json(data: [], code: 201);
        });
        
        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(302)
            ->hasHeader('location', ''); // is '' as PreviousUriUri is not defined
    }
    
    public function testValidationFailsReturnsRedirectResponseWithSpecifiedRedirectUri()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'POST',
                uri: 'foo',
                serverParams: [],
            );
        });
        
        $app->boot(\Tobento\App\Validation\Boot\HttpValidationErrorHandler::class);
        $app->booting();
        
        $app->route('POST', 'foo', function(ValidationRequest $req) {
            
            $validation = $req->validate(
                rules: [
                    'title' => 'required|alpha',
                ],
                redirectUri: '/bar',
            );
            
            return $req->responser()->json(data: [], code: 201);
        });
        
        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(302)
            ->hasHeader('location', '/bar');
    }
    
    public function testValidationFailsReturnsRedirectResponseWithSpecifiedRouteName()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'POST',
                uri: 'foo',
                serverParams: [],
            );
        });
        
        $app->boot(\Tobento\App\Validation\Boot\HttpValidationErrorHandler::class);
        $app->booting();

        $app->route('GET', 'foo/create', function(ValidationRequest $req) {
            return $req->responser()->json(data: [], code: 200);
        })->name('foo.create');
        
        $app->route('POST', 'foo', function(ValidationRequest $req) {
            
            $validation = $req->validate(
                rules: [
                    'title' => 'required|alpha',
                ],
                redirectRouteName: 'foo.create',
            );
            
            return $req->responser()->json(data: [], code: 201);
        });
        
        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(302)
            ->hasHeader('location', '/foo/create');
    }
    
    public function testValidationSuccess()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'POST',
                uri: 'foo',
                serverParams: [],
            )->withParsedBody(['title' => 'lorem']);
        });
        
        $app->boot(\Tobento\App\Validation\Boot\HttpValidationErrorHandler::class);
        $app->booting();
        
        $app->route('POST', 'foo', function(ValidationRequest $req) {
            
            $validation = $req->validate(
                rules: [
                    'title' => 'required|alpha',
                ],
            );
            
            return $req->responser()->json(data: [], code: 201);
        });
        
        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(201);
    }
    
    public function testManuallyValidation()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'POST',
                uri: 'foo',
                serverParams: [],
            );
        });
        
        $app->boot(\Tobento\App\Validation\Boot\HttpValidationErrorHandler::class);
        $app->booting();
        
        $app->route('POST', 'foo', function(ValidationRequest $req) {
            
            $validation = $req->validate(
                rules: [
                    'title' => 'required|alpha',
                ],
                throwExceptionOnFailure: false,
            );
            
            // handle validation...
                        
            return $req->responser()->json(data: ['isValid' => $validation->isValid()], code: 201);
        });
        
        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(201)
            ->isBodySame('{"isValid":false}');
    }
    
    public function testValidationFailsReturnsJsonResponseIfAjax()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'POST',
                uri: 'foo',
                serverParams: [],
            )->withAddedHeader('X-Requested-With', 'XMLHttpRequest');
        });
        
        $app->boot(\Tobento\App\Validation\Boot\HttpValidationErrorHandler::class);
        $app->booting();
        
        $app->route('POST', 'foo', function(ValidationRequest $req) {
            
            $validation = $req->validate(
                rules: [
                    'title' => 'required|alpha',
                ],
                redirectRouteName: 'foo.create',
            );
            
            return $req->responser()->json(data: [], code: 201);
        });
        
        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(422)
            ->isBodySame('{"message":"The title is required.","errors":{"title":["The title is required."]}}');
    }
    
    public function testValidationFailsReturnsJsonResponseIfWantsJson()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'POST',
                uri: 'foo',
                serverParams: [],
            )->withAddedHeader('Accept', 'application/json, text/html');
        });
        
        $app->boot(\Tobento\App\Validation\Boot\HttpValidationErrorHandler::class);
        $app->booting();
        
        $app->route('POST', 'foo', function(ValidationRequest $req) {
            
            $validation = $req->validate(
                rules: [
                    'title' => 'required|alpha',
                ],
            );
            
            return $req->responser()->json(data: [], code: 201);
        });
        
        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(422)
            ->isBodySame('{"message":"The title is required.","errors":{"title":["The title is required."]}}');
    }
}