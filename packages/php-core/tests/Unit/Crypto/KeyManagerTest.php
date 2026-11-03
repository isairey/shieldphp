
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Crypto;

use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Crypto\KeyManager;

final class KeyManagerTest extends TestCase
{
    private const ACTIVE_KEY_ID = 'primary';

    private const OLD_KEY_ID = 'previous';

    private string $primaryKey;

    private string $previousKey;

    private KeyManager $keyManager;

    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('sodium')) {
            self::markTestSkipped(
                'The Sodium extension is required for these tests.'
            );
        }

        $this->primaryKey = str_repeat('A', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        $this->previousKey = str_repeat('B', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);

        $this->keyManager = $this->createKeyManager();
    }

    /**
     * Crea un KeyManager con dos claves válidas.
     *
     * @param string|null $activeKeyId
     */
    private function createKeyManager(
        ?string $activeKeyId = null
    ): KeyManager {
        return new KeyManager(
            [
                self::ACTIVE_KEY_ID => $this->primaryKey,
                self::OLD_KEY_ID => $this->previousKey,
            ],
            $activeKeyId ?? self::ACTIVE_KEY_ID
        );
    }

    /**
     * Comprueba que se pueda crear KeyManager.
     */
    public function testCreatesKeyManagerInstance(): void
    {
        $this->assertInstanceOf(
            KeyManager::class,
            $this->keyManager
        );
    }

    /**
     * Comprueba que getActiveKeyId() devuelva
     * el identificador de la clave activa.
     */
    public function testGetActiveKeyIdReturnsCorrectIdentifier(): void
    {
        $this->assertSame(
            self::ACTIVE_KEY_ID,
            $this->keyManager->getActiveKeyId()
        );
    }

    /**
     * Comprueba que getActiveKey() devuelva
     * el material criptográfico de la clave activa.
     */
    public function testGetActiveKeyReturnsCorrectKey(): void
    {
        $this->assertSame(
            $this->primaryKey,
            $this->keyManager->getActiveKey()
        );
    }

    /**
     * Comprueba que getKey() recupere una clave
     * mediante su identificador.
     */
    public function testGetKeyReturnsRegisteredKey(): void
    {
        $key = $this->keyManager->getKey(
            self::ACTIVE_KEY_ID
        );

        $this->assertSame($this->primaryKey, $key);
    }

    /**
     * Comprueba que se puedan recuperar claves anteriores.
     */
    public function testGetKeyReturnsPreviousKey(): void
    {
        $key = $this->keyManager->getKey(
            self::OLD_KEY_ID
        );

        $this->assertSame($this->previousKey, $key);
    }

    /**
     * Comprueba que hasKey() reconozca una clave registrada.
     */
    public function testHasKeyReturnsTrueForRegisteredKey(): void
    {
        $this->assertTrue(
            $this->keyManager->hasKey(self::ACTIVE_KEY_ID)
        );
    }

    /**
     * Comprueba que hasKey() reconozca las claves anteriores.
     */
    public function testHasKeyReturnsTrueForPreviousKey(): void
    {
        $this->assertTrue(
            $this->keyManager->hasKey(self::OLD_KEY_ID)
        );
    }

    /**
     * Comprueba que hasKey() rechace identificadores desconocidos.
     */
    public function testHasKeyReturnsFalseForUnknownKey(): void
    {
        $this->assertFalse(
            $this->keyManager->hasKey('unknown-key')
        );
    }

    /**
     * Comprueba que getKeyIds() devuelva
     * todos los identificadores registrados.
     */
    public function testGetKeyIdsReturnsRegisteredIdentifiers(): void
    {
        $keyIds = $this->keyManager->getKeyIds();

        $this->assertIsArray($keyIds);
        $this->assertCount(2, $keyIds);

        $this->assertContains(self::ACTIVE_KEY_ID, $keyIds);
        $this->assertContains(self::OLD_KEY_ID, $keyIds);
    }

    /**
     * Comprueba que getKeyIds() no exponga
     * directamente el material criptográfico.
     */
    public function testGetKeyIdsDoesNotExposeKeyMaterial(): void
    {
        $keyIds = $this->keyManager->getKeyIds();

        $this->assertNotContains($this->primaryKey, $keyIds);
        $this->assertNotContains($this->previousKey, $keyIds);
    }

    /**
     * Comprueba que la clave activa esté incluida
     * entre los identificadores registrados.
     */
    public function testActiveKeyIdentifierExists(): void
    {
        $this->assertContains(
            $this->keyManager->getActiveKeyId(),
            $this->keyManager->getKeyIds()
        );
    }

    /**
     * Comprueba que se pueda seleccionar una clave anterior
     * como clave activa al construir otra instancia.
     */
    public function testCanSelectPreviousKeyAsActiveKey(): void
    {
        $manager = $this->createKeyManager(
            self::OLD_KEY_ID
        );

        $this->assertSame(
            self::OLD_KEY_ID,
            $manager->getActiveKeyId()
        );

        $this->assertSame(
            $this->previousKey,
            $manager->getActiveKey()
        );
    }

    /**
     * Comprueba que generateKey() devuelva una cadena.
     */
    public function testGenerateKeyReturnsString(): void
    {
        $key = $this->keyManager->generateKey();

        $this->assertIsString($key);
        $this->assertNotEmpty($key);
    }

    /**
     * Comprueba que generateKey() produzca
     * una clave de exactamente 32 bytes.
     */
    public function testGenerateKeyReturnsCorrectLength(): void
    {
        $key = $this->keyManager->generateKey();

        $this->assertSame(
            SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
            strlen($key)
        );
    }

    /**
     * Comprueba que las claves generadas sean diferentes.
     */
    public function testGenerateKeyProducesDifferentValues(): void
    {
        $firstKey = $this->keyManager->generateKey();
        $secondKey = $this->keyManager->generateKey();

        $this->assertNotSame($firstKey, $secondKey);
    }

    /**
     * Comprueba que una clave generada pueda utilizarse
     * para crear un nuevo KeyManager.
     */
    public function testGeneratedKeyCanBeRegistered(): void
    {
        $generatedKey = $this->keyManager->generateKey();

        $manager = new KeyManager(
            [
                'generated' => $generatedKey,
            ],
            'generated'
        );

        $this->assertSame(
            $generatedKey,
            $manager->getActiveKey()
        );

        $this->assertTrue(
            $manager->hasKey('generated')
        );
    }

    /**
     * Comprueba que decodeBase64Key() recupere
     * correctamente una clave codificada.
     */
    public function testDecodeBase64KeyReturnsOriginalKey(): void
    {
        $encodedKey = base64_encode($this->primaryKey);

        $decodedKey = $this->keyManager->decodeBase64Key(
            $encodedKey
        );

        $this->assertSame(
            $this->primaryKey,
            $decodedKey
        );
    }

    /**
     * Comprueba que una clave generada pueda codificarse
     * y recuperarse mediante Base64.
     */
    public function testBase64KeyEncodingRoundTrip(): void
    {
        $originalKey = $this->keyManager->generateKey();

        $encodedKey = base64_encode($originalKey);

        $decodedKey = $this->keyManager->decodeBase64Key(
            $encodedKey
        );

        $this->assertSame($originalKey, $decodedKey);
    }

    /**
     * Comprueba que la clave decodificada conserve
     * la longitud requerida por libsodium.
     */
    public function testDecodedBase64KeyHasCorrectLength(): void
    {
        $generatedKey = $this->keyManager->generateKey();

        $encodedKey = base64_encode($generatedKey);

        $decodedKey = $this->keyManager->decodeBase64Key(
            $encodedKey
        );

        $this->assertSame(
            SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
            strlen($decodedKey)
        );
    }

    /**
     * Comprueba que dos instancias mantengan
     * sus respectivos conjuntos de claves independientes.
     */
    public function testKeyManagersRemainIndependent(): void
    {
        $firstManager = new KeyManager(
            ['first' => $this->primaryKey],
            'first'
        );

        $secondManager = new KeyManager(
            ['second' => $this->previousKey],
            'second'
        );

        $this->assertTrue($firstManager->hasKey('first'));
        $this->assertFalse($firstManager->hasKey('second'));

        $this->assertTrue($secondManager->hasKey('second'));
        $this->assertFalse($secondManager->hasKey('first'));
    }

    /**
     * Comprueba que consultar las claves no modifique
     * el material criptográfico almacenado.
     */
    public function testReadingKeysDoesNotChangeKeyMaterial(): void
    {
        $originalActiveKey = $this->keyManager->getActiveKey();
        $originalPreviousKey = $this->keyManager->getKey(
            self::OLD_KEY_ID
        );

        $this->keyManager->getActiveKeyId();
        $this->keyManager->getKeyIds();
        $this->keyManager->hasKey(self::ACTIVE_KEY_ID);

        $this->assertSame(
            $originalActiveKey,
            $this->keyManager->getActiveKey()
        );

        $this->assertSame(
            $originalPreviousKey,
            $this->keyManager->getKey(self::OLD_KEY_ID)
        );
    }

    /**
     * Comprueba que varias consultas consecutivas
     * devuelvan resultados consistentes.
     */
    public function testMultipleKeyRetrievalsRemainConsistent(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->assertSame(
                $this->primaryKey,
                $this->keyManager->getActiveKey()
            );

            $this->assertTrue(
                $this->keyManager->hasKey(self::ACTIVE_KEY_ID)
            );
        }
    }
}
