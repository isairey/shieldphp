
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Exceptions;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use ShieldPHP\Core\Exceptions\ExpiredTokenException;
use ShieldPHP\Core\Exceptions\InvalidTokenException;
use ShieldPHP\Core\Exceptions\ShieldException;

final class ExpiredTokenExceptionTest extends TestCase
{
    private const ERROR_MESSAGE = 'The authentication token has expired.';

    private ExpiredTokenException $exception;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exception = new ExpiredTokenException(
            self::ERROR_MESSAGE
        );
    }

    /**
     * Comprueba que se pueda crear ExpiredTokenException.
     */
    public function testCreatesExpiredTokenException(): void
    {
        $exception = new ExpiredTokenException(
            self::ERROR_MESSAGE
        );

        $this->assertInstanceOf(
            ExpiredTokenException::class,
            $exception
        );
    }

    /**
     * Comprueba que herede de InvalidTokenException.
     */
    public function testExtendsInvalidTokenException(): void
    {
        $this->assertInstanceOf(
            InvalidTokenException::class,
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
        $message = 'The access token is no longer valid.';

        $exception = new ExpiredTokenException($message);

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
        $exception = new ExpiredTokenException(
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
        $exception = new ExpiredTokenException(
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
            'Underlying token validation error.'
        );

        $exception = new ExpiredTokenException(
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
        $this->expectException(ExpiredTokenException::class);

        $this->expectExceptionMessage(self::ERROR_MESSAGE);

        throw new ExpiredTokenException(
            self::ERROR_MESSAGE
        );
    }

    /**
     * Comprueba que pueda capturarse mediante
     * su propia clase.
     */
    public function testCanBeCaughtAsExpiredTokenException(): void
    {
        try {
            throw new ExpiredTokenException(
                self::ERROR_MESSAGE
            );
        } catch (ExpiredTokenException $exception) {
            $this->assertSame(
                self::ERROR_MESSAGE,
                $exception->getMessage()
            );

            return;
        }

        self::fail(
            'ExpiredTokenException was not caught.'
        );
    }

    /**
     * Comprueba que pueda capturarse mediante
     * InvalidTokenException.
     */
    public function testCanBeCaughtAsInvalidTokenException(): void
    {
        try {
            throw new ExpiredTokenException(
                self::ERROR_MESSAGE
            );
        } catch (InvalidTokenException $exception) {
            $this->assertInstanceOf(
                ExpiredTokenException::class,
                $exception
            );

            $this->assertSame(
                self::ERROR_MESSAGE,
                $exception->getMessage()
            );

            return;
        }

        self::fail(
            'InvalidTokenException did not catch ExpiredTokenException.'
        );
    }

    /**
     * Comprueba que pueda capturarse mediante
     * ShieldException.
     */
    public function testCanBeCaughtAsShieldException(): void
    {
        try {
            throw new ExpiredTokenException(
                self::ERROR_MESSAGE
            );
        } catch (ShieldException $exception) {
            $this->assertInstanceOf(
                ExpiredTokenException::class,
                $exception
            );

            return;
        }

        self::fail(
            'ShieldException did not catch ExpiredTokenException.'
        );
    }

    /**
     * Comprueba que pueda capturarse mediante
     * RuntimeException.
     */
    public function testCanBeCaughtAsRuntimeException(): void
    {
        try {
            throw new ExpiredTokenException(
                self::ERROR_MESSAGE
            );
        } catch (RuntimeException $exception) {
            $this->assertInstanceOf(
                ExpiredTokenException::class,
                $exception
            );

            return;
        }

        self::fail(
            'RuntimeException did not catch ExpiredTokenException.'
        );
    }

    /**
     * Comprueba que se conserve la cadena
     * de excepciones.
     */
    public function testPreservesExceptionChain(): void
    {
        $originalException = new RuntimeException(
            'JWT decoding failed.'
        );

        $expiredException = new ExpiredTokenException(
            'The JWT has expired.',
            401,
            $originalException
        );

        $this->assertSame(
            $originalException,
            $expiredException->getPrevious()
        );

        $this->assertSame(
            'JWT decoding failed.',
            $expiredException->getPrevious()->getMessage()
        );
    }

    /**
     * Comprueba que se puedan representar
     * diferentes situaciones de expiración.
     */
    public function testSupportsDifferentExpirationMessages(): void
    {
        $messages = [
            'The access token has expired.',
            'The refresh token has expired.',
            'The authentication token is no longer valid.',
            'The session token has expired.',
        ];

        foreach ($messages as $message) {
            $exception = new ExpiredTokenException($message);

            $this->assertInstanceOf(
                ExpiredTokenException::class,
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
        $firstException = new ExpiredTokenException(
            'Access token expired.',
            401
        );

        $secondException = new ExpiredTokenException(
            'Refresh token expired.',
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
