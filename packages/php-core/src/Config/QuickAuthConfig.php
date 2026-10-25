<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Config;

use ShieldPHP\Core\Exceptions\ConfigurationException;

final class QuickAuthConfig
{
    private const DEFAULTS = [
        'app_name' => 'ShieldPHP',
        'access_token_ttl' => 900,
        'refresh_token_ttl' => 604800,
        'secure_cookies' => true,
        'same_site' => 'Lax',
    ];

    private readonly array $settings;

    public function __construct(array $settings = [])
    {
        $this->settings = array_replace(
            self::DEFAULTS,
            $settings
        );

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
     * Comprueba si existe una opción.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->settings);
    }

    /**
     * Obtiene toda la configuración.
     *
     * @return array{
     *     app_name: string,
     *     access_token_ttl: int,
     *     refresh_token_ttl: int,
     *     secure_cookies: bool,
     *     same_site: string
     * }
     */
    public function all(): array
    {
        return $this->settings;
    }
}