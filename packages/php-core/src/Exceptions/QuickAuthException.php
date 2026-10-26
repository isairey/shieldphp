<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Exceptions;

class QuickAuthException extends \RuntimeException
{
    public function __construct(
        string $message = 'Ha ocurrido un error en la librería de seguridad.',
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