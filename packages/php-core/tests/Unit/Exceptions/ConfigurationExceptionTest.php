
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Exceptions;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use ShieldPHP\Core\Exceptions\ConfigurationException;
use ShieldPHP\Core\Exceptions\ShieldException;

final class ConfigurationExceptionTest extends TestCase
{
    private const ERROR_MESSAGE = 'Invalid ShieldPHP configuration.';

    private ConfigurationException $exception;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exception = new ConfigurationException(
            self::ERROR_MESSAGE
        );
    }

    /**
     * Comprueba que se pueda crear ConfigurationException.
     */
    public function testCreatesConfigurationException(): void
    {
        $exception = new ConfigurationException(
            self::ERROR_MESSAGE
        );

        $this->assertInstanceOf(
            ConfigurationException::class,
            $exception
        );
    }

    /**
     * Comprueba que herede de ShieldException.
     */
    public function testExtendsShieldException(): void
    {
        $this->assertInstanceOf(
            ShieldException::class,
            $this->exception
        );
    }

    /**
     * Comprueba que pertenezca a la jerarquía
     * estándar de excepciones de PHP.
     */
    public function testExtendsRuntimeException(): void
    {
        $this->assertInstanceOf(
            RuntimeException::class,
            $this->exception
        );
    }

    /**
     * Comprueba que conserve el mensaje proporcionado.
     */
    public function testReturnsCorrectErrorMessage(): void
    {
        $this->assertSame(
            self::ERROR_MESSAGE,
            $this->exception->getMessage()
        );
    }

    /**
     * Comprueba que acepte un mensaje personalizado.
     */
    public function testAcceptsCustomErrorMessage(): void
    {
        $message = 'The access token TTL is invalid.';

        $exception = new ConfigurationException($message);

        $this->assertSame(
            $message,
            $exception->getMessage()
        );
    }

    /**
     * Comprueba que conserve el código de error.
     */
    public function testReturnsCorrectErrorCode(): void
    {
        $exception = new ConfigurationException(
            self::ERROR_MESSAGE,
            500
        );

        $this->assertSame(500, $exception->getCode());
    }

    /**
     * Comprueba que el código predeterminado sea cero.
     */
    public function testDefaultErrorCodeIsZero(): void
    {
        $exception = new ConfigurationException(
            self::ERROR_MESSAGE
        );

        $this->assertSame(0, $exception->getCode());
    }

    /**
     * Comprueba que conserve una excepción anterior.
     */
    public function testPreservesPreviousException(): void
    {
        $previous = new RuntimeException(
            'Configuration file could not be loaded.'
        );

        $exception = new ConfigurationException(
            self::ERROR_MESSAGE,
            500,
            $previous
        );

        $this->assertSame(
            $previous,
            $exception->getPrevious()
        );
    }

    /**
     * Comprueba que pueda lanzarse correctamente.
     */
    public function testCanBeThrown(): void
    {
        $this->expectException(ConfigurationException::class);

        $this->expectExceptionMessage(self::ERROR_MESSAGE);

        throw new ConfigurationException(
            self::ERROR_MESSAGE
        );
    }

    /**
     * Comprueba que pueda capturarse mediante
     * su propia clase.
     */
    public function testCanBeCaughtAsConfigurationException(): void
    {
        try {
            throw new ConfigurationException(
                self::ERROR_MESSAGE
            );
        } catch (ConfigurationException $exception) {
            $this->assertSame(
                self::ERROR_MESSAGE,
                $exception->getMessage()
            );

            return;
        }

        self::fail(
            'ConfigurationException was not caught.'
        );
    }

    /**
     * Comprueba que pueda capturarse mediante
     * su clase padre ShieldException.
     */
    public function testCanBeCaughtAsShieldException(): void
    {
        try {
            throw new ConfigurationException(
                self::ERROR_MESSAGE
            );
        } catch (ShieldException $exception) {
            $this->assertInstanceOf(
                ConfigurationException::class,
                $exception
            );

            $this->assertSame(
                self::ERROR_MESSAGE,
                $exception->getMessage()
            );

            return;
        }

        self::fail(
            'ShieldException did not catch ConfigurationException.'
        );
    }

    /**
     * Comprueba que también pueda capturarse
     * mediante RuntimeException.
     */
    public function testCanBeCaughtAsRuntimeException(): void
    {
        try {
            throw new ConfigurationException(
                self::ERROR_MESSAGE
            );
        } catch (RuntimeException $exception) {
            $this->assertInstanceOf(
                ConfigurationException::class,
                $exception
            );

            return;
        }

        self::fail(
            'RuntimeException did not catch ConfigurationException.'
        );
    }

    /**
     * Comprueba que se conserve la cadena completa
     * de excepciones.
     */
    public function testPreservesExceptionChain(): void
    {
        $originalException = new RuntimeException(
            'Invalid configuration file.'
        );

        $configurationException = new ConfigurationException(
            'Application configuration could not be initialized.',
            500,
            $originalException
        );

        $this->assertSame(
            $originalException,
            $configurationException->getPrevious()
        );

        $this->assertSame(
            'Invalid configuration file.',
            $configurationException->getPrevious()->getMessage()
        );
    }

    /**
     * Comprueba que se puedan generar excepciones
     * para diferentes errores de configuración.
     */
    public function testSupportsDifferentConfigurationErrors(): void
    {
        $errors = [
            'Invalid application name.',
            'Invalid access token TTL.',
            'Invalid refresh token TTL.',
            'Invalid cookie configuration.',
            'Unsupported SameSite policy.',
        ];

        foreach ($errors as $message) {
            $exception = new ConfigurationException($message);

            $this->assertInstanceOf(
                ConfigurationException::class,
                $exception
            );

            $this->assertSame(
                $message,
                $exception->getMessage()
            );
        }
    }

    /**
     * Comprueba que las instancias mantengan
     * sus mensajes y códigos independientes.
     */
    public function testExceptionInstancesRemainIndependent(): void
    {
        $firstException = new ConfigurationException(
            'Invalid access token configuration.',
            400
        );

        $secondException = new ConfigurationException(
            'Invalid cookie configuration.',
            500
        );

        $this->assertNotSame(
            $firstException->getMessage(),
            $secondException->getMessage()
        );

        $this->assertSame(400, $firstException->getCode());
        $this->assertSame(500, $secondException->getCode());
    }

    /**
     * Comprueba que getTraceAsString() devuelva
     * información de seguimiento como cadena.
     */
    public function testReturnsStringTrace(): void
    {
        $this->assertIsString(
            $this->exception->getTraceAsString()
        );
    }
}
