
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Support;

use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use ShieldPHP\Core\Contracts\ClockInterface;

/**
 * Reloj controlable para pruebas automatizadas.
 *
 * Permite establecer una fecha inicial, consultar el tiempo,
 * avanzar y retroceder el reloj de manera determinista.
 *
 * Útil para probar:
 * - Expiración de tokens JWT.
 * - Expiración de sesiones.
 * - Estados OAuth.
 * - Code verifiers PKCE.
 * - Revocación de tokens.
 *
 * No debe utilizarse como reloj de producción.
 */
final class TestClock implements ClockInterface
{
    /**
     * Fecha y hora actual del reloj de prueba.
     */
    private DateTimeImmutable $currentTime;

    /**
     * Inicializa el reloj con una fecha determinada.
     */
    public function __construct(DateTimeImmutable $initialTime)
    {
        $this->currentTime = $initialTime;
    }

    /**
     * Devuelve la fecha y hora actual del reloj.
     */
    public function now(): DateTimeImmutable
    {
        return $this->currentTime;
    }

    /**
     * Establece una fecha y hora específica.
     *
     * @param DateTimeImmutable $dateTime Nueva fecha y hora.
     */
    public function set(DateTimeImmutable $dateTime): void
    {
        $this->currentTime = $dateTime;
    }

    /**
     * Avanza el reloj utilizando un intervalo.
     */
    public function advance(DateInterval $interval): void
    {
        $this->currentTime = $this->currentTime->add($interval);
    }

    /**
     * Retrocede el reloj utilizando un intervalo.
     */
    public function rewind(DateInterval $interval): void
    {
        $this->currentTime = $this->currentTime->sub($interval);
    }

    /**
     * Avanza el reloj una cantidad determinada de segundos.
     *
     * @throws InvalidArgumentException Si los segundos son negativos.
     */
    public function advanceSeconds(int $seconds): void
    {
        if ($seconds < 0) {
            throw new InvalidArgumentException(
                'The number of seconds cannot be negative.'
            );
        }

        $this->currentTime = $this->currentTime->modify(
            sprintf('+%d seconds', $seconds)
        );
    }

    /**
     * Retrocede el reloj una cantidad determinada de segundos.
     *
     * @throws InvalidArgumentException Si los segundos son negativos.
     */
    public function rewindSeconds(int $seconds): void
    {
        if ($seconds < 0) {
            throw new InvalidArgumentException(
                'The number of seconds cannot be negative.'
            );
        }

        $this->currentTime = $this->currentTime->modify(
            sprintf('-%d seconds', $seconds)
        );
    }

    /**
     * Avanza el reloj una cantidad determinada de minutos.
     */
    public function advanceMinutes(int $minutes): void
    {
        if ($minutes < 0) {
            throw new InvalidArgumentException(
                'The number of minutes cannot be negative.'
            );
        }

        $this->currentTime = $this->currentTime->modify(
            sprintf('+%d minutes', $minutes)
        );
    }

    /**
     * Avanza el reloj una cantidad determinada de horas.
     */
    public function advanceHours(int $hours): void
    {
        if ($hours < 0) {
            throw new InvalidArgumentException(
                'The number of hours cannot be negative.'
            );
        }

        $this->currentTime = $this->currentTime->modify(
            sprintf('+%d hours', $hours)
        );
    }

    /**
     * Avanza el reloj una cantidad determinada de días.
     */
    public function advanceDays(int $days): void
    {
        if ($days < 0) {
            throw new InvalidArgumentException(
                'The number of days cannot be negative.'
            );
        }

        $this->currentTime = $this->currentTime->modify(
            sprintf('+%d days', $days)
        );
    }

    /**
     * Reinicia el reloj a una fecha determinada.
     */
    public function reset(DateTimeImmutable $dateTime): void
    {
        $this->currentTime = $dateTime;
    }

    /**
     * Comprueba si el reloj está en una fecha determinada.
     */
    public function isAt(DateTimeImmutable $dateTime): bool
    {
        return $this->currentTime == $dateTime;
    }

    /**
     * Devuelve la fecha actual en formato ISO 8601.
     */
    public function toIso8601(): string
    {
        return $this->currentTime->format(DATE_ATOM);
    }

    /**
     * Devuelve el timestamp Unix actual.
     */
    public function timestamp(): int
    {
        return $this->currentTime->getTimestamp();
    }
}
