<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Exceptions;

class InvalidTokenException extends ShieldException
{
    public function __construct(
        string $message = 'El token proporcionado no es válido.',
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