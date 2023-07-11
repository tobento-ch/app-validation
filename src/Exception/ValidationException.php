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

namespace Tobento\App\Validation\Exception;

use Tobento\Service\Validation\ValidationInterface;
use RuntimeException;
use Throwable;

/**
 * ValidationException
 */
class ValidationException extends RuntimeException
{
    /**
     * Create a new AuthenticationException.
     *
     * @param ValidationInterface $validation
     * @param string $message The message
     * @param int $code
     * @param null|Throwable $previous
     */
    public function __construct(
        protected ValidationInterface $validation,
        protected null|string $redirectUri = null,
        string $message = '',
        int $code = 0,
        null|Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Returns the validation.
     *
     * @return ValidationInterface
     */
    public function validation(): ValidationInterface
    {
        return $this->validation;
    }
    
    /**
     * Returns the redirect uri.
     *
     * @return null|string
     */
    public function redirectUri(): null|string
    {
        return $this->redirectUri;
    }
}