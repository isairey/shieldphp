
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Support;

use SessionHandlerInterface;
use SessionUpdateTimestampHandlerInterface;

/**
 * Almacenamiento de sesiones en memoria para pruebas.
 *
 * Implementa las interfaces nativas de PHP:
 *
 * - SessionHandlerInterface
 * - SessionUpdateTimestampHandlerInterface
 *
 * Permite almacenar, recuperar, actualizar y eliminar
 * sesiones sin utilizar archivos ni una base de datos.
 *
 * No debe utilizarse en producción.
 */
final class InMemorySessionStore implements
    SessionHandlerInterface,
    SessionUpdateTimestampHandlerInterface
{
    /**
     * Sesiones almacenadas en memoria.
     *
     * Estructura:
     *
     * [
     *     'session-id' => [
     *         'data' => 'serialized-session-data',
     *         'last_accessed_at' => 1234567890,
     *     ],
     * ]
     *
     * @var array<string, array{
     *     data: string,
     *     last_accessed_at: int
     * }>
     */
    private array $sessions = [];

    /**
     * Indica si el almacenamiento está abierto.
     */
    private bool $opened = false;

    /**
     * Abre el almacenamiento de sesiones.
     *
     * @param string $path Directorio de sesiones de PHP.
     * @param string $name Nombre de la sesión.
     */
    public function open(
        string $path,
        string $name
    ): bool {
        $this->opened = true;

        return true;
    }

    /**
     * Cierra el almacenamiento de sesiones.
     */
    public function close(): bool
    {
        $this->opened = false;

        return true;
    }

    /**
     * Lee los datos de una sesión.
     *
     * Si la sesión no existe, devuelve una cadena vacía,
     * indicando a PHP que todavía no contiene información.
     *
     * @param string $id Identificador de sesión.
     *
     * @return string|false Datos serializados o false si el ID es inválido.
     */
    public function read(string $id): string|false
    {
        if ($id === '') {
            return false;
        }

        if (!isset($this->sessions[$id])) {
            return '';
        }

        return $this->sessions[$id]['data'];
    }

    /**
     * Guarda o actualiza los datos de una sesión.
     *
     * @param string $id Identificador de sesión.
     * @param string $data Datos serializados.
     */
    public function write(
        string $id,
        string $data
    ): bool {
        if ($id === '') {
            return false;
        }

        $this->sessions[$id] = [
            'data' => $data,
            'last_accessed_at' => time(),
        ];

        return true;
    }

    /**
     * Destruye una sesión.
     *
     * @param string $id Identificador de sesión.
     */
    public function destroy(string $id): bool
    {
        if ($id === '') {
            return false;
        }

        unset($this->sessions[$id]);

        return true;
    }

    /**
     * Elimina sesiones que superaron su tiempo de vida.
     *
     * @param int $max_lifetime Tiempo máximo de vida en segundos.
     *
     * @return int|false Cantidad de sesiones eliminadas.
     */
    public function gc(int $max_lifetime): int|false
    {
        if ($max_lifetime <= 0) {
            return 0;
        }

        $now = time();
        $removed = 0;

        foreach ($this->sessions as $id => $session) {
            $expiredAt = $session['last_accessed_at']
                + $max_lifetime;

            if ($expiredAt <= $now) {
                unset($this->sessions[$id]);

                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Comprueba si un identificador de sesión es válido.
     *
     * Es utilizado por PHP cuando está habilitado
     * el modo de validación de IDs de sesión.
     */
    public function validateId(string $id): bool
    {
        return $id !== ''
            && isset($this->sessions[$id]);
    }

    /**
     * Actualiza la fecha de último acceso de una sesión.
     *
     * Se utiliza cuando PHP trabaja con lazy write.
     *
     * @param string $id Identificador de sesión.
     * @param string $data Datos serializados.
     */
    public function updateTimestamp(
        string $id,
        string $data
    ): bool {
        if ($id === '' || !isset($this->sessions[$id])) {
            return false;
        }

        $this->sessions[$id] = [
            'data' => $data,
            'last_accessed_at' => time(),
        ];

        return true;
    }

    /**
     * Comprueba si existe una sesión almacenada.
     *
     * Método auxiliar para pruebas.
     */
    public function has(string $id): bool
    {
        return isset($this->sessions[$id]);
    }

    /**
     * Devuelve la cantidad de sesiones almacenadas.
     *
     * Método auxiliar para pruebas.
     */
    public function count(): int
    {
        return count($this->sessions);
    }

    /**
     * Obtiene los datos serializados de una sesión
     * sin consumirla ni modificarla.
     *
     * Método auxiliar para pruebas.
     */
    public function getStoredData(string $id): ?string
    {
        return $this->sessions[$id]['data'] ?? null;
    }

    /**
     * Obtiene la fecha del último acceso.
     *
     * Método auxiliar para pruebas.
     */
    public function getLastAccessedAt(string $id): ?int
    {
        return $this->sessions[$id]['last_accessed_at'] ?? null;
    }

    /**
     * Permite modificar la fecha del último acceso
     * para simular sesiones expiradas en las pruebas.
     *
     * Método auxiliar para pruebas.
     */
    public function setLastAccessedAt(
        string $id,
        int $timestamp
    ): bool {
        if (!isset($this->sessions[$id])) {
            return false;
        }

        $this->sessions[$id]['last_accessed_at'] = $timestamp;

        return true;
    }

    /**
     * Indica si el almacenamiento está abierto.
     *
     * Método auxiliar para pruebas.
     */
    public function isOpened(): bool
    {
        return $this->opened;
    }

    /**
     * Elimina todas las sesiones almacenadas.
     *
     * Método auxiliar para reiniciar las pruebas.
     */
    public function clear(): void
    {
        $this->sessions = [];
    }
}
