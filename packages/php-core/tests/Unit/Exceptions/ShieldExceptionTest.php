
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Exceptions;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use ShieldPHP\Core\Exceptions\AuthenticationException;
use ShieldPHP\Core\Exceptions\ConfigurationException;
use ShieldPHP\Core\Exceptions\EncryptionException;
use ShieldPHP\Core\Exceptions\InvalidTokenException;
use ShieldPHP\Core\Exceptions\QuickAuthException;
use ShieldPHP\Core\Exceptions\ShieldException;

final class ShieldExceptionTest extends TestCase
{
    private const ERROR_MESSAGE = 'ShieldPHP operation failed.';

    private ShieldException $exception;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exception = new ShieldException(
            self::ERROR_MESSAGE
        );
    }

    /**
     * Comprueba que se pueda crear ShieldException.
     */
    public function testCreatesShieldException(): void
    {
        $exception = new ShieldException(
            self::ERROR_MESSAGE
        );

        $this->assertInstanceOf(
            ShieldException::class,
            $exception
        );
    }

    /**
     * Comprueba que ShieldException herede de QuickAuthException.
     */
    public function testExtendsQuickAuthException(): void
    {
        $this->assertInstanceOf(
            QuickAuthException::class,
            $this->exception
        );
    }

    /**
     * Comprueba que ShieldException pertenezca
     * a la jerarquía RuntimeException.
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
     * Comprueba que acepte mensajes personalizados.
     */
    public function testAcceptsCustomErrorMessage(): void
    {
        $message = 'An internal ShieldPHP error occurred.';

        $exception = new ShieldException($message);

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
        $exception = new ShieldException(
            self::ERROR_MESSAGE,
            500
        );

        $this->assertSame(
            500,
            $exception->getCode()
        );
    }

    /**
     * Comprueba que el código predeterminado sea cero.
     */
    public function testDefaultErrorCodeIsZero(): void
    {
        $exception = new ShieldException(
            self::ERROR_MESSAGE
        );

        $this->assertSame(
            0,
            $exception->getCode()
        );
    }

    /**
     * Comprueba que conserve una excepción anterior.
     */
    public function testPreservesPreviousException(): void
    {
        $previous = new RuntimeException(
            'Underlying system error.'
        );

        $exception = new ShieldException(
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
     * Comprueba que se pueda lanzar ShieldException.
     */
    public function testCanBeThrown(): void
    {
        $this->expectException(ShieldException::class);

        $this->expectExceptionMessage(self::ERROR_MESSAGE);

        throw new ShieldException(
            self::ERROR_MESSAGE
        );
    }

    /**
     * Comprueba que pueda capturarse mediante
     * su propia clase.
     */
    public function testCanBeCaughtAsShieldException(): void
    {
        try {
            throw new ShieldException(
                self::ERROR_MESSAGE
            );
        } catch (ShieldException $exception) {
            $this->assertSame(
                self::ERROR_MESSAGE,
                $exception->getMessage()
            );

            return;
        }

        self::fail('ShieldException was not caught.');
    }

    /**
     * Comprueba que pueda capturarse mediante
     * QuickAuthException.
     */
    public function testCanBeCaughtAsQuickAuthException(): void
    {
        try {
            throw new ShieldException(
                self::ERROR_MESSAGE
            );
        } catch (QuickAuthException $exception) {
            $this->assertInstanceOf(
                ShieldException::class,
                $exception
            );

            $this->assertSame(
                self::ERROR_MESSAGE,
                $exception->getMessage()
            );

            return;
        }

        self::fail(
            'QuickAuthException did not catch ShieldException.'
        );
    }

    /**
     * Comprueba que pueda capturarse mediante RuntimeException.
     */
    public function testCanBeCaughtAsRuntimeException(): void
    {
        try {
            throw new ShieldException(
                self::ERROR_MESSAGE
            );
        } catch (RuntimeException $exception) {
            $this->assertInstanceOf(
                ShieldException::class,
                $exception
            );

            return;
        }

        self::fail(
            'RuntimeException did not catch ShieldException.'
        );
    }

    /**
     * Comprueba que las excepciones especializadas
     * hereden de ShieldException.
     */
    public function testSpecializedExceptionsInheritFromShieldException(): void
    {
        $exceptions = [
            new AuthenticationException('Authentication failed.'),
            new ConfigurationException('Invalid configuration.'),
            new EncryptionException('Encryption failed.'),
            new InvalidTokenException('Invalid token.'),
        ];

        foreach ($exceptions as $exception) {
            $this->assertInstanceOf(
                ShieldException::class,
                $exception
            );
        }
    }

    /**
     * Comprueba que las excepciones especializadas
     * puedan capturarse mediante ShieldException.
     */
    public function testCanCatchSpecializedExceptions(): void
    {
        $exceptions = [
            new AuthenticationException('Authentication failed.'),
            new ConfigurationException('Invalid configuration.'),
            new EncryptionException('Encryption failed.'),
            new InvalidTokenException('Invalid token.'),
        ];

        foreach ($exceptions as $exception) {
            try {
                throw $exception;
            } catch (ShieldException $caughtException) {
                $this->assertSame(
                    $exception,
                    $caughtException
                );

                continue;
            }

            self::fail(
                'A specialized ShieldPHP exception was not caught.'
            );
        }
    }

    /**
     * Comprueba que una clase pueda extender ShieldException.
     */
    public function testSupportsCustomExceptionInheritance(): void
    {
        $customException = new class(
            'Custom ShieldPHP exception.'
        ) extends ShieldException {
        };

        $this->assertInstanceOf(
            ShieldException::class,
            $customException
        );

        $this->assertSame(
            'Custom ShieldPHP exception.',
            $customException->getMessage()
        );
    }

    /**
     * Comprueba que preserve cadenas de excepciones.
     */
    public function testPreservesExceptionChain(): void
    {
        $originalException = new RuntimeException(
            'Database connection failed.'
        );

        $intermediateException = new ShieldException(
            'An internal operation failed.',
            500,
            $originalException
        );

        $finalException = new ShieldException(
            'ShieldPHP could not complete the operation.',
            500,
            $intermediateException
        );

        $this->assertSame(
            $intermediateException,
            $finalException->getPrevious()
        );

        $this->assertSame(
            $originalException,
            $finalException->getPrevious()->getPrevious()
        );

        $this->assertSame(
            'Database connection failed.',
            $finalException
                ->getPrevious()
                ->getPrevious()
                ->getMessage()
        );
    }

    /**
     * Comprueba que las instancias mantengan
     * sus mensajes y códigos independientes.
     */
    public function testExceptionInstancesRemainIndependent(): void
    {
        $firstException = new ShieldException(
            'First error.',
            400
        );

        $secondException = new ShieldException(
            'Second error.',
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

    /**
     * Comprueba que las excepciones especializadas
     * conserven sus respectivos mensajes.
     */
    public function testSpecializedExceptionsPreserveTheirMessages(): void
    {
        $authenticationException = new AuthenticationException(
            'Authentication failed.'
        );

        $configurationException = new ConfigurationException(
            'Configuration failed.'
        );

        $encryptionException = new EncryptionException(
            'Encryption failed.'
        );

        $invalidTokenException = new InvalidTokenException(
            'Token validation failed.'
        );

        $this->assertSame(
            'Authentication failed.',
            $authenticationException->getMessage()
        );

        $this->assertSame(
            'Configuration failed.',
            $configurationException->getMessage()
        );

        $this->assertSame(
            'Encryption failed.',
            $encryptionException->getMessage()
        );

        $this->assertSame(
            'Token validation failed.',
            $invalidTokenException->getMessage()
        );
    }
}
