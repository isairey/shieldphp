<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Exceptions;

final class ConfigurationException extends ShieldException
{
    public function __construct(
        string $message = 'La configuración de ShieldPHP no es válida.',
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