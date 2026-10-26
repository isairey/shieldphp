<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Exceptions;

class ShieldException extends QuickAuthException
{
    public function __construct(
        string $message = 'Ha ocurrido un error en ShieldPHP.',
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