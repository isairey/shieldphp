<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Contracts;

use DateTimeImmutable;

interface ClockInterface
{
    /**
     * Obtiene la fecha y hora actuales.
     */
    public function now(): DateTimeImmutable;
}