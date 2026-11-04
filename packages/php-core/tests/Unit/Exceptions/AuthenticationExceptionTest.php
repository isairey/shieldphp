
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Exceptions;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use ShieldPHP\Core\Exceptions\AuthenticationException;
use ShieldPHP\Core\Exceptions\InvalidCredentialsException;
use ShieldPHP\Core\Exceptions\ShieldException;
use Throwable;

final class AuthenticationExceptionTest extends TestCase
{
    private const ERROR_MESSAGE = 'Authentication failed.';

    private AuthenticationException $exception;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exception = new AuthenticationException(
            self::ERROR_MESSAGE
        );
    }

    /**
     * Comprueba que se pueda crear AuthenticationException.
     */
    public function testCreatesAuthenticationException(): void
    {
        $exception = new AuthenticationException(
            self::ERROR_MESSAGE
        );

        $this->assertInstanceOf(
            AuthenticationException::class,
            $exception
        );
    }

    /**
     * Comprueba que la excepción herede de ShieldException.
     */
    public function testExtendsShieldException(): void
    {
        $this->assertInstanceOf(
            ShieldException::class,
            $this->exception
        );
    }

    /**
     * Comprueba que la excepción pertenezca
     * a la jerarquía estándar de excepciones PHP.
     */
    public function testExtendsRuntimeException(): void
    {
        $this->assertInstanceOf(
            RuntimeException::class,
            $this->exception
        );
    }

    /**
     * Comprueba que se conserve el mensaje proporcionado.
     */
    public function testReturnsCorrectErrorMessage(): void
    {
        $this->assertSame(
            self::ERROR_MESSAGE,
            $this->exception->getMessage()
        );
    }

    /**
     * Comprueba que se conserve el código de error.
     */
    public function testReturnsCorrectErrorCode(): void
    {
        $exception = new AuthenticationException(
            self::ERROR_MESSAGE,
            401
        );

        $this->assertSame(
            401,
            $exception->getCode()
        );
    }

    /**
     * Comprueba que el código predeterminado sea cero
     * cuando no se proporciona uno.
     */
    public function testDefaultErrorCodeIsZero(): void
    {
        $exception = new AuthenticationException(
            self::ERROR_MESSAGE
        );

        $this->assertSame(0, $exception->getCode());
    }

    /**
     * Comprueba que se conserve la excepción anterior.
     */
    public function testPreservesPreviousException(): void
    {
        $previous = new RuntimeException(
            'Underlying authentication error.'
        );

        $exception = new AuthenticationException(
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
     * Comprueba que la excepción pueda lanzarse correctamente.
     */
    public function testCanBeThrown(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->expectExceptionMessage(self::ERROR_MESSAGE);

        throw new AuthenticationException(
            self::ERROR_MESSAGE
        );
    }

    /**
     * Comprueba que la excepción pueda capturarse
     * utilizando su propia clase.
     */
    public function testCanBeCaughtAsAuthenticationException(): void
    {
        try {
            throw new AuthenticationException(
                self::ERROR_MESSAGE
            );
        } catch (AuthenticationException $exception) {
            $this->assertSame(
                self::ERROR_MESSAGE,
                $exception->getMessage()
            );

            return;
        }

        self::fail(
            'AuthenticationException was not caught.'
        );
    }

    /**
     * Comprueba que también pueda capturarse
     * mediante su clase padre ShieldException.
     */
    public function testCanBeCaughtAsShieldException(): void
    {
        try {
            throw new AuthenticationException(
                self::ERROR_MESSAGE
            );
        } catch (ShieldException $exception) {
            $this->assertInstanceOf(
                AuthenticationException::class,
                $exception
            );

            $this->assertSame(
                self::ERROR_MESSAGE,
                $exception->getMessage()
            );

            return;
        }

        self::fail(
            'ShieldException did not catch AuthenticationException.'
        );
    }

    /**
     * Comprueba que InvalidCredentialsException
     * herede de AuthenticationException.
     */
    public function testInvalidCredentialsExceptionInheritsFromAuthenticationException(): void
    {
        $exception = new InvalidCredentialsException(
            'Invalid credentials.'
        );

        $this->assertInstanceOf(
            AuthenticationException::class,
            $exception
        );
    }

    /**
     * Comprueba que una excepción derivada pueda capturarse
     * mediante AuthenticationException.
     */
    public function testCatchesInvalidCredentialsExceptionAsAuthenticationException(): void
    {
        try {
            throw new InvalidCredentialsException(
                'Invalid credentials.'
            );
        } catch (AuthenticationException $exception) {
            $this->assertInstanceOf(
                InvalidCredentialsException::class,
                $exception
            );

            $this->assertSame(
                'Invalid credentials.',
                $exception->getMessage()
            );

            return;
        }

        self::fail(
            'InvalidCredentialsException was not caught.'
        );
    }

    /**
     * Comprueba que se mantenga la cadena de excepciones.
     */
    public function testPreservesExceptionChain(): void
    {
        $originalException = new RuntimeException(
            'Database connection failed.'
        );

        $authenticationException = new AuthenticationException(
            'Authentication could not be completed.',
            500,
            $originalException
        );

        $this->assertSame(
            $originalException,
            $authenticationException->getPrevious()
        );

        $this->assertSame(
            'Database connection failed.',
            $authenticationException->getPrevious()->getMessage()
        );
    }

    /**
     * Comprueba que cada instancia mantenga
     * su propio mensaje y código.
     */
    public function testExceptionInstancesRemainIndependent(): void
    {
        $firstException = new AuthenticationException(
            'First authentication error.',
            401
        );

        $secondException = new AuthenticationException(
            'Second authentication error.',
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
     * información de seguimiento en formato de cadena.
     */
    public function testReturnsStringTrace(): void
    {
        $this->assertIsString(
            $this->exception->getTraceAsString()
        );
    }
}
