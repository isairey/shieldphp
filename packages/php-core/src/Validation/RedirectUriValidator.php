
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Validation;

use InvalidArgumentException;
use ShieldPHP\Core\Exceptions\ConfigurationException;

final class RedirectUriValidator
{
    private const MAX_URI_LENGTH = 2048;

    public function __construct(
        private readonly bool $allowHttpLocalhost = false
    ) {
    }

    /**
     * Comprueba que una URI esté registrada y sea segura.
     *
     * La comparación con las URI registradas es exacta:
     * no se permiten cambios de host, puerto, ruta, esquema o query.
     *
     * @param list<string> $registeredUris URI autorizadas por la aplicación.
     *
     * @throws InvalidArgumentException Si la URI solicitada no está permitida.
     * @throws ConfigurationException Si la lista de URI registradas es inválida.
     */
    public function validate(
        string $redirectUri,
        array $registeredUris
    ): void {
        if ($registeredUris === [] || !array_is_list($registeredUris)) {
            throw new ConfigurationException(
                'Debe configurarse una lista de URI de redirección permitidas.'
            );
        }

        $this->assertValidFormat($redirectUri);

        $matched = false;

        foreach ($registeredUris as $registeredUri) {
            if (!is_string($registeredUri)) {
                throw new ConfigurationException(
                    'Cada URI registrada debe ser una cadena.'
                );
            }

            try {
                $this->assertValidFormat($registeredUri);
            } catch (InvalidArgumentException $exception) {
                throw new ConfigurationException(
                    'La configuración contiene una URI registrada inválida.',
                    0,
                    $exception
                );
            }

            if (hash_equals($registeredUri, $redirectUri)) {
                $matched = true;
            }
        }

        if (!$matched) {
            throw new InvalidArgumentException(
                'La URI de redirección no está registrada ni autorizada.'
            );
        }
    }

    /**
     * Comprueba si una URI tiene un formato permitido.
     */
    public function isValid(string $uri): bool
    {
        try {
            $this->assertValidFormat($uri);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Comprueba la estructura y las condiciones de seguridad de la URI.
     */
    public function assertValidFormat(string $uri): void
    {
        if (
            $uri === ''
            || strlen($uri) > self::MAX_URI_LENGTH
            || preg_match('/[\x00-\x20\x7F\\\\]/', $uri) === 1
            || preg_match('/%(?![a-fA-F0-9]{2})/', $uri) === 1
        ) {
            throw new InvalidArgumentException(
                'La URI de redirección tiene un formato inválido.'
            );
        }

        $parts = parse_url($uri);

        if (
            $parts === false
            || !isset($parts['scheme'], $parts['host'])
            || $parts['host'] === ''
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['fragment'])
        ) {
            throw new InvalidArgumentException(
                'La URI debe ser absoluta, tener un host válido '
                . 'y no contener credenciales ni fragmentos.'
            );
        }

        $scheme = strtolower($parts['scheme']);

        $this->validateHost($parts['host']);

        if (isset($parts['port'])) {
            if (
                !is_int($parts['port'])
                || $parts['port'] < 1
                || $parts['port'] > 65535
            ) {
                throw new InvalidArgumentException(
                    'El puerto de la URI no es válido.'
                );
            }
        }

        if ($scheme === 'https') {
            return;
        }

        $host = strtolower(trim($parts['host'], '[]'));

        $isLocalhost = in_array(
            $host,
            ['localhost', '127.0.0.1', '::1'],
            true
        );

        if (
            $scheme === 'http'
            && $this->allowHttpLocalhost
            && $isLocalhost
        ) {
            return;
        }

        throw new InvalidArgumentException(
            'Las URI de redirección deben utilizar HTTPS. '
            . 'HTTP solamente está permitido en localhost durante desarrollo.'
        );
    }

    /**
     * Comprueba que el host sea una IP válida o un nombre DNS válido.
     */
    private function validateHost(string $host): void
    {
        $normalizedHost = trim($host, '[]');

        if (filter_var($normalizedHost, FILTER_VALIDATE_IP) !== false) {
            return;
        }

        if (
            strlen($normalizedHost) > 253
            || preg_match(
                '/\A(?=.{1,253}\z)'
                . '[a-zA-Z0-9]'
                . '(?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?'
                . '(?:\.[a-zA-Z0-9]'
                . '(?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*\z/',
                $normalizedHost
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'El host de la URI no es válido.'
            );
        }
    }
}
