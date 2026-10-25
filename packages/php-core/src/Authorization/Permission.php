<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Authorization;

use InvalidArgumentException;

final class Permission
{
    private readonly string $name;
    private readonly ?string $description;

    public function __construct(
        string $name,
        ?string $description = null
    ) {
        $name = trim($name);

        if (
            preg_match(
                '/\A[a-zA-Z0-9][a-zA-Z0-9._:-]{0,127}\z/',
                $name
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'El identificador del permiso no es válido.'
            );
        }

        if ($description !== null) {
            $description = trim($description);

            if ($description === '') {
                $description = null;
            }

            if (
                $description !== null
                && strlen($description) > 500
            ) {
                throw new InvalidArgumentException(
                    'La descripción no puede superar los 500 bytes.'
                );
            }
        }

        $this->name = $name;
        $this->description = $description;
    }

    /**
     * Obtiene el identificador único del permiso.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Obtiene la descripción del permiso.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Comprueba si coincide con otro permiso.
     */
    public function equals(Permission $permission): bool
    {
        return $this->name === $permission->name;
    }

    /**
     * Convierte el permiso en un array.
     *
     * @return array{name: string, description: ?string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}