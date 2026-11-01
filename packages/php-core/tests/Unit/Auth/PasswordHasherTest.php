
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Auth\PasswordHasher;

final class PasswordHasherTest extends TestCase
{
    private const PASSWORD = 'SecurePassword123!';

    private PasswordHasher $passwordHasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordHasher = new PasswordHasher();
    }

    /**
     * Comprueba que hash() genere una cadena.
     */
    public function testHashReturnsString(): void
    {
        $hash = $this->passwordHasher->hash(self::PASSWORD);

        $this->assertIsString($hash);
        $this->assertNotEmpty($hash);
    }

    /**
     * Comprueba que el hash generado no contenga
     * la contraseña original en texto plano.
     */
    public function testHashDoesNotExposeOriginalPassword(): void
    {
        $hash = $this->passwordHasher->hash(self::PASSWORD);

        $this->assertNotSame(self::PASSWORD, $hash);
        $this->assertStringNotContainsString(self::PASSWORD, $hash);
    }

    /**
     * Comprueba que una contraseña correcta coincida con su hash.
     */
    public function testVerifyAcceptsCorrectPassword(): void
    {
        $hash = $this->passwordHasher->hash(self::PASSWORD);

        $result = $this->passwordHasher->verify(
            self::PASSWORD,
            $hash
        );

        $this->assertTrue($result);
    }

    /**
     * Comprueba que una contraseña incorrecta sea rechazada.
     */
    public function testVerifyRejectsIncorrectPassword(): void
    {
        $hash = $this->passwordHasher->hash(self::PASSWORD);

        $result = $this->passwordHasher->verify(
            'WrongPassword123!',
            $hash
        );

        $this->assertFalse($result);
    }

    /**
     * Comprueba que dos hashes de la misma contraseña
     * sean diferentes debido al uso de salts aleatorios.
     */
    public function testGeneratesDifferentHashesForSamePassword(): void
    {
        $firstHash = $this->passwordHasher->hash(self::PASSWORD);
        $secondHash = $this->passwordHasher->hash(self::PASSWORD);

        $this->assertNotSame($firstHash, $secondHash);

        $this->assertTrue(
            $this->passwordHasher->verify(
                self::PASSWORD,
                $firstHash
            )
        );

        $this->assertTrue(
            $this->passwordHasher->verify(
                self::PASSWORD,
                $secondHash
            )
        );
    }

    /**
     * Comprueba que una contraseña modificada no coincida
     * aunque tenga una longitud similar.
     */
    public function testVerifyRejectsModifiedPassword(): void
    {
        $originalPassword = 'MySecurePassword123!';
        $modifiedPassword = 'MySecurePassword124!';

        $hash = $this->passwordHasher->hash($originalPassword);

        $this->assertFalse(
            $this->passwordHasher->verify(
                $modifiedPassword,
                $hash
            )
        );
    }

    /**
     * Comprueba que un hash inválido sea rechazado.
     */
    public function testVerifyRejectsInvalidHash(): void
    {
        $result = $this->passwordHasher->verify(
            self::PASSWORD,
            'invalid-password-hash'
        );

        $this->assertFalse($result);
    }

    /**
     * Comprueba que un hash vacío sea rechazado.
     */
    public function testVerifyRejectsEmptyHash(): void
    {
        $result = $this->passwordHasher->verify(
            self::PASSWORD,
            ''
        );

        $this->assertFalse($result);
    }

    /**
     * Comprueba que la contraseña vacía no coincida
     * con un hash de otra contraseña.
     */
    public function testVerifyRejectsEmptyPasswordAgainstValidHash(): void
    {
        $hash = $this->passwordHasher->hash(self::PASSWORD);

        $result = $this->passwordHasher->verify('', $hash);

        $this->assertFalse($result);
    }

    /**
     * Comprueba que las contraseñas con caracteres Unicode
     * puedan almacenarse y verificarse.
     */
    public function testSupportsUnicodePasswords(): void
    {
        $password = 'ContraseñaSegura_áéíóú_2026!';

        $hash = $this->passwordHasher->hash($password);

        $this->assertTrue(
            $this->passwordHasher->verify($password, $hash)
        );

        $this->assertFalse(
            $this->passwordHasher->verify(
                'ContrasenaSegura_aeiou_2026!',
                $hash
            )
        );
    }

    /**
     * Comprueba que una contraseña larga, dentro del límite
     * configurado de 4096 bytes, pueda procesarse.
     */
    public function testSupportsPasswordWithinMaximumLength(): void
    {
        $password = str_repeat('A', 4096);

        $hash = $this->passwordHasher->hash($password);

        $this->assertNotEmpty($hash);

        $this->assertTrue(
            $this->passwordHasher->verify($password, $hash)
        );
    }

    /**
     * Comprueba que un hash recién generado no necesite
     * una actualización inmediata.
     */
    public function testNewHashDoesNotNeedRehash(): void
    {
        $hash = $this->passwordHasher->hash(self::PASSWORD);

        $this->assertFalse(
            $this->passwordHasher->needsRehash($hash)
        );
    }

    /**
     * Comprueba que needsRehash() devuelva un booleano.
     */
    public function testNeedsRehashReturnsBoolean(): void
    {
        $hash = $this->passwordHasher->hash(self::PASSWORD);

        $result = $this->passwordHasher->needsRehash($hash);

        $this->assertIsBool($result);
    }

    /**
     * Comprueba que el hash generado sea reconocido
     * por el sistema nativo de contraseñas de PHP.
     */
    public function testGeneratesRecognizedPasswordHash(): void
    {
        $hash = $this->passwordHasher->hash(self::PASSWORD);

        $information = password_get_info($hash);

        $this->assertNotSame('unknown', $information['algoName']);
    }

    /**
     * Comprueba que un hash pueda verificarse varias veces
     * sin modificarse durante las verificaciones.
     */
    public function testHashCanBeVerifiedMultipleTimes(): void
    {
        $hash = $this->passwordHasher->hash(self::PASSWORD);

        $firstResult = $this->passwordHasher->verify(
            self::PASSWORD,
            $hash
        );

        $secondResult = $this->passwordHasher->verify(
            self::PASSWORD,
            $hash
        );

        $this->assertTrue($firstResult);
        $this->assertTrue($secondResult);
    }
}
