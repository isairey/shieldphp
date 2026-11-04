
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Exceptions;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use ShieldPHP\Core\Exceptions\ExpiredTokenException;
use ShieldPHP\Core\Exceptions\InvalidTokenException;
use ShieldPHP\Core\Exceptions\ShieldException;

final class InvalidTokenExceptionTest extends TestCase
{
    private const ERROR_MESSAGE = 'Invalid authentication token.';

    private InvalidTokenException $exception;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exception = new InvalidTokenException(
            self::ERROR_MESSAGE
        );
    }

    /**
     * Comprueba que se pueda crear InvalidTokenException.
     */
    public function testCreatesInvalidTokenException(): void
    {
        $exception = new InvalidTokenException(
            self::ERROR_MESSAGE
        );

        $this->assertInstanceOf(
            InvalidTokenException::class,
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
        $message = 'The JWT signature is invalid.';

        $exception = new InvalidTokenException($message);

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
        $exception = new InvalidTokenException(
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
        $exception = new InvalidTokenException(
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
            'JWT signature verification failed.'
        );

        $exception = new InvalidTokenException(
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
        $this->expectException(InvalidTokenException::class);

        $this->expectExceptionMessage(self::ERROR_MESSAGE);

        throw new InvalidTokenException(
            self::ERROR_MESSAGE
        );
    }

    /**
     * Comprueba que pueda capturarse mediante
     * su propia clase.
     */
    public function testCanBeCaughtAsInvalidTokenException(): void
    {
        try {
            throw new InvalidTokenException(
                self::ERROR_MESSAGE
            );
        } catch (InvalidTokenException $exception) {
            $this->assertSame(
                self::ERROR_MESSAGE,
                $exception->getMessage()
            );

            return;
        }

        self::fail(
            'InvalidTokenException was not caught.'
        );
    }

    /**
     * Comprueba que pueda capturarse mediante
     * ShieldException.
     */
    public function testCanBeCaughtAsShieldException(): void
    {
        try {
            throw new InvalidTokenException(
                self::ERROR_MESSAGE
            );
        } catch (ShieldException $exception) {
            $this->assertInstanceOf(
                InvalidTokenException::class,
                $exception
            );

            $this->assertSame(
                self::ERROR_MESSAGE,
                $exception->getMessage()
            );

            return;
        }

        self::fail(
            'ShieldException did not catch InvalidTokenException.'
        );
    }

    /**
     * Comprueba que pueda capturarse mediante
     * RuntimeException.
     */
    public function testCanBeCaughtAsRuntimeException(): void
    {
        try {
            throw new InvalidTokenException(
                self::ERROR_MESSAGE
            );
        } catch (RuntimeException $exception) {
            $this->assertInstanceOf(
                InvalidTokenException::class,
                $exception
            );

            return;
        }

        self::fail(
            'RuntimeException did not catch InvalidTokenException.'
        );
    }

    /**
     * Comprueba que ExpiredTokenException herede
     * de InvalidTokenException.
     */
    public function testExpiredTokenExceptionInheritsFromInvalidTokenException(): void
    {
        $exception = new ExpiredTokenException(
            'The authentication token has expired.'
        );

        $this->assertInstanceOf(
            InvalidTokenException::class,
            $exception
        );
    }

    /**
     * Comprueba que una excepción de token expirado
     * pueda capturarse como InvalidTokenException.
     */
    public function testCatchesExpiredTokenExceptionAsInvalidTokenException(): void
    {
        try {
            throw new ExpiredTokenException(
                'The authentication token has expired.'
            );
        } catch (InvalidTokenException $exception) {
            $this->assertInstanceOf(
                ExpiredTokenException::class,
                $exception
            );

            $this->assertSame(
                'The authentication token has expired.',
                $exception->getMessage()
            );

            return;
        }

        self::fail(
            'ExpiredTokenException was not caught as InvalidTokenException.'
        );
    }

    /**
     * Comprueba que se conserven las excepciones anteriores.
     */
    public function testPreservesExceptionChain(): void
    {
        $originalException = new RuntimeException(
            'JWT decoding failed.'
        );

        $invalidTokenException = new InvalidTokenException(
            'Token validation failed.',
            401,
            $originalException
        );

        $this->assertSame(
            $originalException,
            $invalidTokenException->getPrevious()
        );

        $this->assertSame(
            'JWT decoding failed.',
            $invalidTokenException->getPrevious()->getMessage()
        );
    }

    /**
     * Comprueba que pueda utilizarse para diferentes
     * situaciones relacionadas con tokens inválidos.
     */
    public function testSupportsDifferentTokenErrorMessages(): void
    {
        $messages = [
            'Invalid authentication token.',
            'The JWT signature is invalid.',
            'The token issuer is not trusted.',
            'The token audience is invalid.',
            'The token has an invalid format.',
            'The token has not reached its activation time.',
            'The token has been revoked.',
        ];

        foreach ($messages as $message) {
            $exception = new InvalidTokenException($message);

            $this->assertInstanceOf(
                InvalidTokenException::class,
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
        $firstException = new InvalidTokenException(
            'Invalid token.',
            401
        );

        $secondException = new InvalidTokenException(
            'Revoked token.',
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
