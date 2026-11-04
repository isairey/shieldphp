
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Exceptions;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use ShieldPHP\Core\Exceptions\EncryptionException;
use ShieldPHP\Core\Exceptions\ShieldException;

final class EncryptionExceptionTest extends TestCase
{
    private const ERROR_MESSAGE = 'Encryption operation failed.';

    private EncryptionException $exception;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exception = new EncryptionException(
            self::ERROR_MESSAGE
        );
    }

    /**
     * Comprueba que se pueda crear EncryptionException.
     */
    public function testCreatesEncryptionException(): void
    {
        $exception = new EncryptionException(
            self::ERROR_MESSAGE
        );

        $this->assertInstanceOf(
            EncryptionException::class,
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
        $message = 'Unable to decrypt encrypted data.';

        $exception = new EncryptionException($message);

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
        $exception = new EncryptionException(
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
        $exception = new EncryptionException(
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
            'Underlying cryptographic error.'
        );

        $exception = new EncryptionException(
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
        $this->expectException(EncryptionException::class);

        $this->expectExceptionMessage(self::ERROR_MESSAGE);

        throw new EncryptionException(
            self::ERROR_MESSAGE
        );
    }

    /**
     * Comprueba que pueda capturarse mediante
     * su propia clase.
     */
    public function testCanBeCaughtAsEncryptionException(): void
    {
        try {
            throw new EncryptionException(
                self::ERROR_MESSAGE
            );
        } catch (EncryptionException $exception) {
            $this->assertSame(
                self::ERROR_MESSAGE,
                $exception->getMessage()
            );

            return;
        }

        self::fail(
            'EncryptionException was not caught.'
        );
    }

    /**
     * Comprueba que pueda capturarse mediante
     * su clase padre ShieldException.
     */
    public function testCanBeCaughtAsShieldException(): void
    {
        try {
            throw new EncryptionException(
                self::ERROR_MESSAGE
            );
        } catch (ShieldException $exception) {
            $this->assertInstanceOf(
                EncryptionException::class,
                $exception
            );

            $this->assertSame(
                self::ERROR_MESSAGE,
                $exception->getMessage()
            );

            return;
        }

        self::fail(
            'ShieldException did not catch EncryptionException.'
        );
    }

    /**
     * Comprueba que también pueda capturarse
     * mediante RuntimeException.
     */
    public function testCanBeCaughtAsRuntimeException(): void
    {
        try {
            throw new EncryptionException(
                self::ERROR_MESSAGE
            );
        } catch (RuntimeException $exception) {
            $this->assertInstanceOf(
                EncryptionException::class,
                $exception
            );

            return;
        }

        self::fail(
            'RuntimeException did not catch EncryptionException.'
        );
    }

    /**
     * Comprueba que se conserve la cadena
     * de excepciones.
     */
    public function testPreservesExceptionChain(): void
    {
        $originalException = new RuntimeException(
            'Sodium encryption operation failed.'
        );

        $encryptionException = new EncryptionException(
            'Encryption could not be completed.',
            500,
            $originalException
        );

        $this->assertSame(
            $originalException,
            $encryptionException->getPrevious()
        );

        $this->assertSame(
            'Sodium encryption operation failed.',
            $encryptionException->getPrevious()->getMessage()
        );
    }

    /**
     * Comprueba que pueda utilizarse para diferentes
     * situaciones relacionadas con errores criptográficos.
     */
    public function testSupportsDifferentCryptographicErrors(): void
    {
        $errors = [
            'Encryption operation failed.',
            'Decryption operation failed.',
            'Invalid encryption key.',
            'Invalid encrypted payload.',
            'Authentication verification failed.',
            'Unsupported encryption format.',
            'Invalid ciphertext.',
            'Cryptographic operation failed.',
        ];

        foreach ($errors as $message) {
            $exception = new EncryptionException($message);

            $this->assertInstanceOf(
                EncryptionException::class,
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
        $firstException = new EncryptionException(
            'Encryption failed.',
            500
        );

        $secondException = new EncryptionException(
            'Decryption failed.',
            400
        );

        $this->assertNotSame(
            $firstException->getMessage(),
            $secondException->getMessage()
        );

        $this->assertSame(
            500,
            $firstException->getCode()
        );

        $this->assertSame(
            400,
            $secondException->getCode()
        );
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
