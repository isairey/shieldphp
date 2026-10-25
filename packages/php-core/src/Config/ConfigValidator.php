<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Config;

use ShieldPHP\Core\Exceptions\ConfigurationException;

final class ConfigValidator
{
    /**
     * Opciones permitidas en la configuración de ShieldPHP.
     *
     * @var list<string>
     */
    private const ALLOWED_KEYS = [
        'app_name',
        'access_token_ttl',
        'refresh_token_ttl',
        'secure_cookies',
        'same_site',
    ];

    /**
     * Valores válidos para SameSite.
     *
     * @var list<string>
     */
    private const ALLOWED_SAME_SITE_VALUES = [
        'Strict',
        'Lax',
        'None',
    ];

    /**
     * Valida la configuración completa de ShieldPHP.
     *
     * @param array<string, mixed> $settings
     *
     * @throws ConfigurationException Si alguna opción es inválida.
     */
    public static function validate(array $settings): void
    {
        self::validateKeys($settings);
        self::validateAppName($settings);
        self::validateTokenLifetimes($settings);
        self::validateCookieSettings($settings);
    }

    /**
     * Comprueba que no existan opciones desconocidas.
     *
     * @param array<string, mixed> $settings
     */
    private static function validateKeys(array $settings): void
    {
        $unknownKeys = array_diff(
            array_keys($settings),
            self::ALLOWED_KEYS
        );

        if ($unknownKeys !== []) {
            throw new ConfigurationException(
                'Opciones de configuración desconocidas: '
                . implode(', ', $unknownKeys)
            );
        }

        foreach (self::ALLOWED_KEYS as $key) {
            if (!array_key_exists($key, $settings)) {
                throw new ConfigurationException(
                    sprintf(
                        'Falta la opción de configuración "%s".',
                        $key
                    )
                );
            }
        }
    }

    /**
     * Valida el nombre de la aplicación.
     *
     * @param array<string, mixed> $settings
     */
    private static function validateAppName(array $settings): void
    {
        $appName = $settings['app_name'];

        if (
            !is_string($appName)
            || trim($appName) === ''
            || strlen($appName) > 100
        ) {
            throw new ConfigurationException(
                'app_name debe ser una cadena no vacía de hasta 100 bytes.'
            );
        }
    }

    /**
     * Valida los tiempos de vida de los tokens.
     *
     * @param array<string, mixed> $settings
     */
    private static function validateTokenLifetimes(
        array $settings
    ): void {
        $accessTokenTtl = $settings['access_token_ttl'];
        $refreshTokenTtl = $settings['refresh_token_ttl'];

        if (
            !is_int($accessTokenTtl)
            || $accessTokenTtl <= 0
        ) {
            throw new ConfigurationException(
                'access_token_ttl debe ser un entero positivo expresado en segundos.'
            );
        }

        if (
            !is_int($refreshTokenTtl)
            || $refreshTokenTtl <= 0
        ) {
            throw new ConfigurationException(
                'refresh_token_ttl debe ser un entero positivo expresado en segundos.'
            );
        }

        if ($refreshTokenTtl <= $accessTokenTtl) {
            throw new ConfigurationException(
                'refresh_token_ttl debe ser mayor que access_token_ttl.'
            );
        }
    }

    /**
     * Valida la configuración de las cookies.
     *
     * @param array<string, mixed> $settings
     */
    private static function validateCookieSettings(
        array $settings
    ): void {
        $secureCookies = $settings['secure_cookies'];
        $sameSite = $settings['same_site'];

        if (!is_bool($secureCookies)) {
            throw new ConfigurationException(
                'secure_cookies debe ser un valor booleano.'
            );
        }

        if (
            !is_string($sameSite)
            || !in_array(
                $sameSite,
                self::ALLOWED_SAME_SITE_VALUES,
                true
            )
        ) {
            throw new ConfigurationException(
                'same_site debe ser Strict, Lax o None.'
            );
        }

        if ($sameSite === 'None' && !$secureCookies) {
            throw new ConfigurationException(
                'Las cookies con SameSite=None requieren Secure=true.'
            );
        }
    }
}