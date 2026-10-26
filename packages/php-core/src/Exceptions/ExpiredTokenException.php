<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Exceptions;

final class ExpiredTokenException extends InvalidTokenException
{
    public function __construct(
        string $message = 'El token ha expirado.',
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