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

namespace Tobento\App\Validation\Test\Exception;

use PHPUnit\Framework\TestCase;
use Tobento\App\Validation\Exception\ValidationException;
use Tobento\Service\Validation\Validator;
use RuntimeException;

/**
 * ValidationExceptionTest
 */
class ValidationExceptionTest extends TestCase
{
    public function testException()
    {
        $validator = new Validator();
        $validation = $validator->validating(
            value: 'foo',
            rules: 'alpha|minLen:2',
        );
        
        $e = new ValidationException(
            validation: $validation,
            redirectUri: 'uri',
        );
        
        $this->assertInstanceof(RuntimeException::class, $e);
        $this->assertSame($validation, $e->validation());
        $this->assertSame('uri', $e->redirectUri());
    }
}