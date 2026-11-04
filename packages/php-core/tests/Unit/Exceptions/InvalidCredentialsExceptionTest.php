
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Exceptions;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use ShieldPHP\Core\Exceptions\AuthenticationException;
use ShieldPHP\Core\Exceptions\InvalidCredentialsException;
use ShieldPHP\Core\Exceptions\ShieldException;

final class InvalidCredentialsExceptionTest extends TestCase
{
    private const ERROR_MESSAGE = 'Invalid credentials.';

    private InvalidCredentialsException $exception;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exception = new InvalidCredentialsException(
            self::ERROR_MESSAGE
        );
    }

    /**
     * Comprueba que se pueda crear InvalidCredentialsException.
     */
    public function testCreatesInvalidCredentialsException(): void
    {
        $exception = new InvalidCredentialsException(
            self::ERROR_MESSAGE
        );

        $this->assertInstanceOf(
            InvalidCredentialsException::class,
            $exception
        );
    }

    /**
     * Comprueba que herede de AuthenticationException.
     */
    public function testExtendsAuthenticationException(): void
    {
        $this->assertInstanceOf(
            AuthenticationException::class,
            $this->exception
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
        $message = 'Authentication credentials are not valid.';

        $exception = new InvalidCredentialsException($message);

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
        $exception = new InvalidCredentialsException(
            self::ERROR_MESSAGE,
            401
        );

        $this->assertSame(
            401,
            $exception->getCode()
        );
    }

    /**
     * Comprueba que el código predeterminado sea cero.
     */
    public function testDefaultErrorCodeIsZero(): void
    {
        $exception = new InvalidCredentialsException(
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
            'Underlying authentication error.'
        );

        $exception = new InvalidCredentialsException(
            self::ERROR_MESSAGE,
            401,
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
        $this->expectException(
            InvalidCredentialsException::class
        );

        $this->expectExceptionMessage(self::ERROR_MESSAGE);

        throw new InvalidCredentialsException(
            self::ERROR_MESSAGE
        );
    }

    /**
     * Comprueba que pueda capturarse mediante
     * su propia clase.
     */
    public function testCanBeCaughtAsInvalidCredentialsException(): void
    {
        try {
            throw new InvalidCredentialsException(
                self::ERROR_MESSAGE
            );
        } catch (InvalidCredentialsException $exception) {
            $this->assertSame(
                self::ERROR_MESSAGE,
                $exception->getMessage()
            );

            return;
        }

        self::fail(
            'InvalidCredentialsException was not caught.'
        );
    }

    /**
     * Comprueba que pueda capturarse mediante
     * AuthenticationException.
     */
    public function testCanBeCaughtAsAuthenticationException(): void
    {
        try {
            throw new InvalidCredentialsException(
                self::ERROR_MESSAGE
            );
        } catch (AuthenticationException $exception) {
            $this->assertInstanceOf(
                InvalidCredentialsException::class,
                $exception
            );

            $this->assertSame(
                self::ERROR_MESSAGE,
                $exception->getMessage()
            );

            return;
        }

        self::fail(
            'AuthenticationException did not catch InvalidCredentialsException.'
        );
    }

    /**
     * Comprueba que pueda capturarse mediante
     * ShieldException.
     */
    public function testCanBeCaughtAsShieldException(): void
    {
        try {
            throw new InvalidCredentialsException(
                self::ERROR_MESSAGE
            );
        } catch (ShieldException $exception) {
            $this->assertInstanceOf(
                InvalidCredentialsException::class,
                $exception
            );

            return;
        }

        self::fail(
            'ShieldException did not catch InvalidCredentialsException.'
        );
    }

    /**
     * Comprueba que pueda capturarse mediante
     * RuntimeException.
     */
    public function testCanBeCaughtAsRuntimeException(): void
    {
        try {
            throw new InvalidCredentialsException(
                self::ERROR_MESSAGE
            );
        } catch (RuntimeException $exception) {
            $this->assertInstanceOf(
                InvalidCredentialsException::class,
                $exception
            );

            return;
        }

        self::fail(
            'RuntimeException did not catch InvalidCredentialsException.'
        );
    }

    /**
     * Comprueba que se conserve la cadena
     * de excepciones.
     */
    public function testPreservesExceptionChain(): void
    {
        $originalException = new RuntimeException(
            'Credential verification failed.'
        );

        $credentialsException = new InvalidCredentialsException(
            'Invalid credentials.',
            401,
            $originalException
        );

        $this->assertSame(
            $originalException,
            $credentialsException->getPrevious()
        );

        $this->assertSame(
            'Credential verification failed.',
            $credentialsException->getPrevious()->getMessage()
        );
    }

    /**
     * Comprueba que se puedan utilizar diferentes
     * mensajes para los errores de credenciales.
     */
    public function testSupportsDifferentCredentialErrorMessages(): void
    {
        $messages = [
            'Invalid credentials.',
            'Incorrect email or password.',
            'Authentication failed.',
            'Unable to verify credentials.',
        ];

        foreach ($messages as $message) {
            $exception = new InvalidCredentialsException($message);

            $this->assertInstanceOf(
                InvalidCredentialsException::class,
                $exception
            );

            $this->assertSame(
                $message,
                $exception->getMessage()
            );
        }
    }

    /**
     * Comprueba que diferentes instancias mantengan
     * sus mensajes y códigos independientes.
     */
    public function testExceptionInstancesRemainIndependent(): void
    {
        $firstException = new InvalidCredentialsException(
            'Invalid credentials.',
            401
        );

        $secondException = new InvalidCredentialsException(
            'Authentication failed.',
            403
        );

        $this->assertNotSame(
            $firstException->getMessage(),
            $secondException->getMessage()
        );

        $this->assertSame(401, $firstException->getCode());
        $this->assertSame(403, $secondException->getCode());
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
