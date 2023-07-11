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

namespace Tobento\App\Validation\Test\Boot;

use PHPUnit\Framework\TestCase;
use Tobento\App\AppInterface;
use Tobento\App\AppFactory;
use Tobento\App\Validation\Boot\Validator;
use Tobento\Service\Validation\ValidatorInterface;
use Tobento\Service\Validation\RulesInterface;
use Tobento\Service\Validation\Rule\Type;
use Tobento\Service\Translation\TranslatorInterface;
use Tobento\Service\Translation\Resource;
use Tobento\Service\Filesystem\Dir;

/**
 * ValidatorTest
 */
class ValidatorTest extends TestCase
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
        
        return $app;
    }
    
    public static function tearDownAfterClass(): void
    {
        (new Dir())->delete(__DIR__.'/../app/');
    }
    
    public function testInterfacesAreAvailable()
    {
        $app = $this->createApp();
        $app->boot(Validator::class);
        $app->booting();
        
        $this->assertInstanceof(ValidatorInterface::class, $app->get(ValidatorInterface::class));
    }
    
    public function testValidateData()
    {
        $app = $this->createApp();
        $app->boot(Validator::class);
        $app->booting();
        
        $validator = $app->get(ValidatorInterface::class);
        
        $validation = $validator->validating(
            value: 'foo',
            rules: 'alpha|minLen:2',
        );
        
        $this->assertTrue($validation->isValid());
    }
    
    public function testAddingRules()
    {
        $app = $this->createApp();
        $app->boot(Validator::class);
        $app->booting();
        
        $app->on(RulesInterface::class, function(RulesInterface $rules) {
            $rules->add('isBool', [new Type(), 'bool']);
        });
        
        $validator = $app->get(ValidatorInterface::class);
        
        $validation = $validator->validating(
            value: true,
            rules: 'isBool',
        );
        
        $this->assertTrue($validation->isValid());
    }
    
    public function testMessageTranslation()
    {
        $app = $this->createApp();
        $app->boot(\Tobento\App\Translation\Boot\Translation::class);
        $app->boot(Validator::class);
        $app->booting();
        
        $app->on(TranslatorInterface::class, function(TranslatorInterface $translator) {
            $translator->resources()->add(new Resource('*', 'de', [
                'username' => 'Benutzername',
            ]));
        });
        
        $validator = $app->get(ValidatorInterface::class);
        $translator = $app->get(TranslatorInterface::class);
        
        $validation = $validator->validating(value: '55', rules: 'alpha', key: 'username');
        
        $this->assertSame(
            'The username must only contain letters [a-zA-Z]',
            $validation->errors()->first()->message(),
        );
        
        $translator->setLocale('de');
        
        $validation = $validator->validating(value: '55', rules: 'alpha', key: 'username');
        
        $this->assertSame(
            'Benutzername darf nur Buchstaben [a-zA-Z] enthalten.',
            $validation->errors()->first()->message(),
        );
    }
}