<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Config;

use ShieldPHP\Core\Exceptions\ConfigurationException;

final class ShieldConfig
{
    /**
     * Valores predeterminados de ShieldPHP.
     */
    private const DEFAULTS = [
        'app_name' => 'ShieldPHP',
        'access_token_ttl' => 900,
        'refresh_token_ttl' => 604800,
        'secure_cookies' => true,
        'same_site' => 'Lax',
    ];

    /**
     * Configuración validada e inmutable.
     */
    private readonly array $settings;

    public function __construct(array $settings = [])
    {
        // Combinar los valores predeterminados con los personalizados.
        $this->settings = array_replace(
            self::DEFAULTS,
            $settings
        );

        // Validar todas las opciones antes de utilizarlas.
        ConfigValidator::validate($this->settings);
    }

    /**
     * Obtiene una opción de configuración.
     */
    public function get(string $key): mixed
    {
        if (!array_key_exists($key, $this->settings)) {
            throw new ConfigurationException(
                sprintf(
                    'La opción de configuración "%s" no existe.',
                    $key
                )
            );
        }

        return $this->settings[$key];
    }

    /**
     * Comprueba si existe una opción de configuración.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->settings);
    }

    /**
     * Devuelve todas las opciones de configuración.
     */
    public function all(): array
    {
        return $this->settings;
    }
}