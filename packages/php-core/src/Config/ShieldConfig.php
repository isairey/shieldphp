<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Config;

use ShieldPHP\Core\Exceptions\ConfigurationException;

final class ShieldConfig
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
        $this->settings = array_replace(self::DEFAULTS, $settings);

        ConfigValidator::validate($this->settings);
    }

    public function get(string $key): mixed
    {
        if (!array_key_exists($key, $this->settings)) {
            throw new ConfigurationException(
                sprintf('La opción "%s" no existe.', $key)
            );
        }

        return $this->settings[$key];
    }

    public function all(): array
    {
        return $this->settings;
    }
}