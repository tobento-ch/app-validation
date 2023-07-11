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

namespace Tobento\App\Validation\Boot;

use Tobento\App\Http\Boot\ErrorHandler;
use Tobento\App\Validation\Exception\ValidationException;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Uri\PreviousUriInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * HttpValidationErrorHandler boot.
 */
class HttpValidationErrorHandler extends ErrorHandler
{
    public const INFO = [
        'boot' => [
            'Http Validation Error Handler',
        ],
    ];
    
    protected const HANDLER_PRIORITY = 3000;
    
   /**
     * Handle a throwable.
     *
     * @param Throwable $t
     * @return Throwable|ResponseInterface Return throwable if cannot handle, otherwise anything.
     */
    public function handleThrowable(Throwable $t): Throwable|ResponseInterface
    {
        if (! $t instanceof ValidationException) {
            return $t;
        }
        
        $requester = $this->app->get(RequesterInterface::class);

        if ($requester->wantsJson() || $requester->isAjax()) {
            return $this->jsonResponse($t);
        }
        
        return $this->redirectResponse($t);
    }

    /**
     * Returns the redirect response for validation exception.
     *
     * @param ValidationException $e
     * @return ResponseInterface
     */
    protected function redirectResponse(ValidationException $e): ResponseInterface
    {
        $responser = $this->app->get(ResponserInterface::class);
        
        $uri = (string)$this->app->get(PreviousUriInterface::class);

        if (!empty($e->redirectUri())) {
            $uri = $e->redirectUri();
        }
        
        if (!empty($e->getMessage())) {
            $responser->messages()->add('error', $e->getMessage());
        }
        
        $responser->messages()->push($e->validation()->errors());
        
        return $responser
            ->withInput($e->validation()->data()->all())
            ->redirect(uri: $uri);
    }
    
    /**
     * Returns the json response for validation exception.
     *
     * @param ValidationException $e
     * @return ResponseInterface
     */
    protected function jsonResponse(ValidationException $e): ResponseInterface
    {
        $message = $e->getMessage();
        
        if (empty($message)) {
            $message = (string)$e->validation()->errors()->first()?->message();
        }
        
        $errors = [];
        
        foreach($e->validation()->errors() as $error) {
            $errors[$error->key()][] = $error->message();
        }
        
        return $this->app->get(ResponserInterface::class)->json(
            data: [
                'message' => $message,
                'errors' => $errors,
            ],
            code: 422,
        );
    }
}