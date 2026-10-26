<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Exceptions;

final class InvalidCredentialsException extends AuthenticationException
{
    public function __construct(
        string $message = 'Las credenciales proporcionadas no son válidas.',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            $message,
            $code,
            $previous
        );
    }
}