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

namespace Tobento\App\Validation\Http;

use Tobento\Service\Validation\ValidatorInterface;
use Tobento\Service\Validation\ValidationInterface;
use Tobento\App\Validation\Exception\ValidationException;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Routing\UrlException;

/**
 * ValidationRequest
 */
final class ValidationRequest
{
    /**
     * Create a new ValidationRequest.
     *
     * @param ValidatorInterface $validator
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @param RouterInterface $router
     */
    public function __construct(
        protected ValidatorInterface $validator,
        protected RequesterInterface $requester,
        protected ResponserInterface $responser,
        protected RouterInterface $router,
    ) {}
    
    /**
     * Validate the request.
     * 
     * @param array $rules
     * @param null|string $redirectUri
     * @return ValidationInterface
     * @throws ValidationException
     */
    public function validate(
        array $rules,
        null|string $redirectUri = null,
        null|string $redirectRouteName = null,
        string $errorMessage = '',
        bool $throwExceptionOnFailure = true,
    ): ValidationInterface {
        
        $validation = $this->validator->validate(
            data: $this->requester->input()->all(),
            rules: $rules,
        );
        
        if (! $throwExceptionOnFailure) {
            return $validation;
        }
        
        if ($validation->isValid()) {
            return $validation;
        }
        
        if (!empty($redirectRouteName)) {
            try {
                $redirectUri = $this->router->url($redirectRouteName)->get();
            } catch (UrlException $e) {
                // ignore
            }
        }
        
        throw new ValidationException(
            validation: $validation,
            redirectUri: $redirectUri,
            message: $errorMessage,
        );
    }
    
    /**
     * Returns a new instance with the specified validator.
     * 
     * @param ValidatorInterface $validator
     * @return static
     */
    public function withValidator(ValidatorInterface $validator): static
    {
        $new = clone $this;
        $new->validator = $validator;
        return $new;
    }
    
    /**
     * Returns a new instance with the specified requester.
     * 
     * @param RequesterInterface $requester
     * @return static
     */
    public function withRequester(RequesterInterface $requester): static
    {
        $new = clone $this;
        $new->requester = $requester;
        return $new;
    }
    
    /**
     * Returns a new instance with the specified responser.
     * 
     * @param ResponserInterface $responser
     * @return static
     */
    public function withResponser(ResponserInterface $responser): static
    {
        $new = clone $this;
        $new->responser = $responser;
        return $new;
    }
    
    /**
     * Returns a new instance with the specified router.
     * 
     * @param RouterInterface $router
     * @return static
     */
    public function withRouter(RouterInterface $router): static
    {
        $new = clone $this;
        $new->router = $router;
        return $new;
    }

    /**
     * Returns the requester.
     * 
     * @return RequesterInterface
     */
    public function requester(): RequesterInterface
    {
        return $this->requester;
    }
    
    /**
     * Returns the responser.
     * 
     * @return ResponserInterface
     */
    public function responser(): ResponserInterface
    {
        return $this->responser;
    }
    
    /**
     * Returns the router.
     * 
     * @return RouterInterface
     */
    public function router(): RouterInterface
    {
        return $this->router;
    }
}