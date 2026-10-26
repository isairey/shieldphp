<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Exceptions;

final class EncryptionException extends ShieldException
{
    public function __construct(
        string $message = 'Ha ocurrido un error criptográfico en ShieldPHP.',
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