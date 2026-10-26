<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Exceptions;

final class AuthenticationException extends ShieldException
{
    /**
     * Crea una excepción de autenticación.
     */
    public function __construct(
        string $message = 'La autenticación ha fallado.',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}