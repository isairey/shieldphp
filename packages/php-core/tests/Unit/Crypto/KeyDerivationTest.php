
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Crypto;

use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Crypto\KeyDerivation;

final class KeyDerivationTest extends TestCase
{
    private const PASSWORD = 'ShieldPHP-SecurePassword-2026!';

    private const KEY_LENGTH = 32;

    private KeyDerivation $keyDerivation;

    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('sodium')) {
            self::markTestSkipped(
                'The Sodium extension is required for these tests.'
            );
        }

        $this->keyDerivation = new KeyDerivation();
    }

    /**
     * Genera un salt válido para las pruebas.
     */
    private function createSalt(): string
    {
        return random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
    }

    /**
     * Comprueba que se pueda crear KeyDerivation.
     */
    public function testCreatesKeyDerivationInstance(): void
    {
        $this->assertInstanceOf(
            KeyDerivation::class,
            $this->keyDerivation
        );
    }

    /**
     * Comprueba que generateSalt() devuelva una cadena.
     */
    public function testGenerateSaltReturnsString(): void
    {
        $salt = $this->keyDerivation->generateSalt();

        $this->assertIsString($salt);
        $this->assertNotEmpty($salt);
    }

    /**
     * Comprueba que el salt tenga la longitud requerida
     * por Sodium Password Hashing.
     */
    public function testGenerateSaltReturnsCorrectLength(): void
    {
        $salt = $this->keyDerivation->generateSalt();

        $this->assertSame(
            SODIUM_CRYPTO_PWHASH_SALTBYTES,
            strlen($salt)
        );
    }

    /**
     * Comprueba que cada salt generado sea diferente.
     */
    public function testGenerateSaltProducesUniqueValues(): void
    {
        $firstSalt = $this->keyDerivation->generateSalt();
        $secondSalt = $this->keyDerivation->generateSalt();

        $this->assertNotSame($firstSalt, $secondSalt);
    }

    /**
     * Comprueba que deriveKey() devuelva una cadena.
     */
    public function testDeriveKeyReturnsString(): void
    {
        $salt = $this->createSalt();

        $key = $this->keyDerivation->deriveKey(
            self::PASSWORD,
            $salt
        );

        $this->assertIsString($key);
        $this->assertNotEmpty($key);
    }

    /**
     * Comprueba que la clave derivada tenga 32 bytes.
     */
    public function testDeriveKeyReturns32Bytes(): void
    {
        $salt = $this->createSalt();

        $key = $this->keyDerivation->deriveKey(
            self::PASSWORD,
            $salt
        );

        $this->assertSame(
            self::KEY_LENGTH,
            strlen($key)
        );
    }

    /**
     * Comprueba que la misma contraseña y el mismo salt
     * produzcan exactamente la misma clave.
     *
     * La derivación debe ser determinista.
     */
    public function testDerivesSameKeyWithSamePasswordAndSalt(): void
    {
        $salt = $this->createSalt();

        $firstKey = $this->keyDerivation->deriveKey(
            self::PASSWORD,
            $salt
        );

        $secondKey = $this->keyDerivation->deriveKey(
            self::PASSWORD,
            $salt
        );

        $this->assertSame($firstKey, $secondKey);
    }

    /**
     * Comprueba que una contraseña diferente
     * produzca una clave diferente.
     */
    public function testDifferentPasswordsProduceDifferentKeys(): void
    {
        $salt = $this->createSalt();

        $firstKey = $this->keyDerivation->deriveKey(
            'FirstSecurePassword123!',
            $salt
        );

        $secondKey = $this->keyDerivation->deriveKey(
            'SecondSecurePassword456!',
            $salt
        );

        $this->assertNotSame($firstKey, $secondKey);
    }

    /**
     * Comprueba que salts diferentes produzcan
     * claves diferentes para la misma contraseña.
     */
    public function testDifferentSaltsProduceDifferentKeys(): void
    {
        $firstSalt = $this->createSalt();
        $secondSalt = $this->createSalt();

        $firstKey = $this->keyDerivation->deriveKey(
            self::PASSWORD,
            $firstSalt
        );

        $secondKey = $this->keyDerivation->deriveKey(
            self::PASSWORD,
            $secondSalt
        );

        $this->assertNotSame($firstKey, $secondKey);
    }

    /**
     * Comprueba que la clave derivada no sea
     * la contraseña original.
     */
    public function testDerivedKeyDoesNotExposePassword(): void
    {
        $salt = $this->createSalt();

        $key = $this->keyDerivation->deriveKey(
            self::PASSWORD,
            $salt
        );

        $this->assertNotSame(self::PASSWORD, $key);
    }

    /**
     * Comprueba que se puedan derivar claves
     * utilizando contraseñas Unicode.
     */
    public function testSupportsUnicodePasswords(): void
    {
        $password = 'ContraseñaSegura_áéíóú_ñ_🔐';

        $salt = $this->createSalt();

        $firstKey = $this->keyDerivation->deriveKey(
            $password,
            $salt
        );

        $secondKey = $this->keyDerivation->deriveKey(
            $password,
            $salt
        );

        $this->assertSame(
            self::KEY_LENGTH,
            strlen($firstKey)
        );

        $this->assertSame($firstKey, $secondKey);
    }

    /**
     * Comprueba que los salts generados puedan utilizarse
     * directamente para derivar claves.
     */
    public function testGeneratedSaltCanBeUsedForKeyDerivation(): void
    {
        $salt = $this->keyDerivation->generateSalt();

        $key = $this->keyDerivation->deriveKey(
            self::PASSWORD,
            $salt
        );

        $this->assertSame(
            self::KEY_LENGTH,
            strlen($key)
        );
    }

    /**
     * Comprueba que dos instancias independientes
     * produzcan la misma clave con los mismos parámetros.
     */
    public function testDifferentInstancesDeriveSameKey(): void
    {
        $firstInstance = new KeyDerivation();
        $secondInstance = new KeyDerivation();

        $salt = $this->createSalt();

        $firstKey = $firstInstance->deriveKey(
            self::PASSWORD,
            $salt
        );

        $secondKey = $secondInstance->deriveKey(
            self::PASSWORD,
            $salt
        );

        $this->assertSame($firstKey, $secondKey);
    }

    /**
     * Comprueba que la derivación sea compatible
     * con Argon2id y los parámetros interactivos de Sodium.
     */
    public function testUsesArgon2idWithInteractiveLimits(): void
    {
        $salt = $this->createSalt();

        $derivedKey = $this->keyDerivation->deriveKey(
            self::PASSWORD,
            $salt
        );

        $expectedKey = sodium_crypto_pwhash(
            self::KEY_LENGTH,
            self::PASSWORD,
            $salt,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13
        );

        $this->assertSame($expectedKey, $derivedKey);
    }

    /**
     * Comprueba que los datos binarios de la contraseña
     * puedan procesarse sin corromper la derivación.
     */
    public function testSupportsBinaryPasswordData(): void
    {
        $password = "\x00\x01\x02\xFF\xFE";
        $salt = $this->createSalt();

        $firstKey = $this->keyDerivation->deriveKey(
            $password,
            $salt
        );

        $secondKey = $this->keyDerivation->deriveKey(
            $password,
            $salt
        );

        $this->assertSame(
            self::KEY_LENGTH,
            strlen($firstKey)
        );

        $this->assertSame($firstKey, $secondKey);
    }

    /**
     * Comprueba que las claves derivadas sean diferentes
     * cuando cambia un solo byte de la contraseña.
     */
    public function testSmallPasswordChangeProducesDifferentKey(): void
    {
        $salt = $this->createSalt();

        $firstKey = $this->keyDerivation->deriveKey(
            'SecurePassword123!',
            $salt
        );

        $secondKey = $this->keyDerivation->deriveKey(
            'SecurePassword124!',
            $salt
        );

        $this->assertNotSame($firstKey, $secondKey);
    }

    /**
     * Comprueba que varias derivaciones independientes
     * no interfieran entre sí.
     */
    public function testMultipleDerivationsRemainIndependent(): void
    {
        $firstSalt = $this->createSalt();
        $secondSalt = $this->createSalt();

        $firstKey = $this->keyDerivation->deriveKey(
            self::PASSWORD,
            $firstSalt
        );

        $secondKey = $this->keyDerivation->deriveKey(
            self::PASSWORD,
            $secondSalt
        );

        $thirdKey = $this->keyDerivation->deriveKey(
            self::PASSWORD,
            $firstSalt
        );

        $this->assertSame($firstKey, $thirdKey);
        $this->assertNotSame($firstKey, $secondKey);
    }
}
