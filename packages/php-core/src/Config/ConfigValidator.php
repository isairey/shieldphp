<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Config;

use ShieldPHP\Core\Exceptions\ConfigurationException;

final class ConfigValidator
{
    private const ALLOWED_KEYS = [
        'app_name',
        'access_token_ttl',
        'refresh_token_ttl',
        'secure_cookies',
        'same_site',
    ];

    public static function validate(array $settings): void
    {
        $unknownKeys = array_diff(
            array_keys($settings),
            self::ALLOWED_KEYS
        );

        if ($unknownKeys !== []) {
            throw new ConfigurationException(
                'Opciones desconocidas: ' . implode(', ', $unknownKeys)
            );
        }

        if (
            !is_string($settings['app_name'])
            || trim($settings['app_name']) === ''
        ) {
            throw new ConfigurationException(
                'app_name debe ser una cadena no vacía.'
            );
        }

        foreach (['access_token_ttl', 'refresh_token_ttl'] as $key) {
            if (!is_int($settings[$key]) || $settings[$key] <= 0) {
                throw new ConfigurationException(
                    "{$key} debe ser un entero positivo."
                );
            }
        }

        if ($settings['refresh_token_ttl'] <= $settings['access_token_ttl']) {
            throw new ConfigurationException(
                'refresh_token_ttl debe ser mayor que access_token_ttl.'
            );
        }

        if (!is_bool($settings['secure_cookies'])) {
            throw new ConfigurationException(
                'secure_cookies debe ser booleano.'
            );
        }

        if (
            !in_array(
                $settings['same_site'],
                ['Strict', 'Lax', 'None'],
                true
            )
        ) {
            throw new ConfigurationException(
                'same_site debe ser Strict, Lax o None.'
            );
        }

        if (
            $settings['same_site'] === 'None'
            && $settings['secure_cookies'] !== true
        ) {
            throw new ConfigurationException(
                'SameSite=None requiere secure_cookies=true.'
            );
        }
    }
}