
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Crypto;

use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Crypto\Encryption;
use ShieldPHP\Core\Exceptions\EncryptionException;

final class EncryptionTest extends TestCase
{
    private const KEY = 'shieldphp-encryption-test-key-0001';

    private const PREFIX = 'shp:v1:';

    private Encryption $encryption;

    protected function setUp(): void
    {
        parent::setUp();

        $this->encryption = $this->createEncryption();
    }

    /**
     * Crea una instancia de Encryption con una clave válida.
     */
    private function createEncryption(?string $key = null): Encryption
    {
        $key ??= self::KEY;

        return new Encryption($key);
    }

    /**
     * Extrae el contenido binario de un mensaje cifrado.
     */
    private function decodePayload(string $encrypted): string
    {
        $this->assertStringStartsWith(
            self::PREFIX,
            $encrypted
        );

        $payload = substr($encrypted, strlen(self::PREFIX));

        $decoded = base64_decode($payload, true);

        if ($decoded === false) {
            throw new \RuntimeException(
                'Encrypted payload is not valid Base64.'
            );
        }

        return $decoded;
    }

    /**
     * Construye un mensaje cifrado a partir de datos binarios.
     */
    private function encodePayload(string $payload): string
    {
        return self::PREFIX . base64_encode($payload);
    }

    /**
     * Comprueba que una instancia de Encryption pueda crearse.
     */
    public function testCreatesEncryptionInstance(): void
    {
        $this->assertInstanceOf(
            Encryption::class,
            $this->encryption
        );
    }

    /**
     * Comprueba que encrypt() devuelva una cadena.
     */
    public function testEncryptReturnsString(): void
    {
        $encrypted = $this->encryption->encrypt(
            'Hello ShieldPHP'
        );

        $this->assertIsString($encrypted);
        $this->assertNotEmpty($encrypted);
    }

    /**
     * Comprueba que el mensaje cifrado tenga
     * el prefijo de versión esperado.
     */
    public function testEncryptUsesCorrectFormatPrefix(): void
    {
        $encrypted = $this->encryption->encrypt(
            'Sensitive information'
        );

        $this->assertStringStartsWith(
            self::PREFIX,
            $encrypted
        );
    }

    /**
     * Comprueba que el contenido cifrado
     * tenga una representación Base64 válida.
     */
    public function testEncryptedPayloadUsesBase64Encoding(): void
    {
        $encrypted = $this->encryption->encrypt(
            'Sensitive information'
        );

        $payload = substr($encrypted, strlen(self::PREFIX));

        $decoded = base64_decode($payload, true);

        $this->assertNotFalse($decoded);
        $this->assertNotEmpty($decoded);
    }

    /**
     * Comprueba que el texto original no aparezca
     * directamente en el resultado cifrado.
     */
    public function testEncryptDoesNotExposePlaintext(): void
    {
        $plaintext = 'My confidential information 2026';

        $encrypted = $this->encryption->encrypt($plaintext);

        $this->assertNotSame($plaintext, $encrypted);

        $this->assertStringNotContainsString(
            $plaintext,
            $encrypted
        );
    }

    /**
     * Comprueba que el mensaje pueda cifrarse y descifrarse.
     */
    public function testEncryptAndDecryptRoundTrip(): void
    {
        $plaintext = 'Hello ShieldPHP';

        $encrypted = $this->encryption->encrypt($plaintext);

        $decrypted = $this->encryption->decrypt($encrypted);

        $this->assertSame($plaintext, $decrypted);
    }

    /**
     * Comprueba que se pueda cifrar una cadena vacía.
     */
    public function testEncryptAndDecryptEmptyString(): void
    {
        $plaintext = '';

        $encrypted = $this->encryption->encrypt($plaintext);

        $decrypted = $this->encryption->decrypt($encrypted);

        $this->assertNotEmpty($encrypted);
        $this->assertSame($plaintext, $decrypted);
    }

    /**
     * Comprueba que se puedan cifrar caracteres Unicode.
     */
    public function testEncryptAndDecryptUnicodeText(): void
    {
        $plaintext = 'Contraseña segura: áéíóú, ñ, € y 漢字 🔐';

        $encrypted = $this->encryption->encrypt($plaintext);

        $decrypted = $this->encryption->decrypt($encrypted);

        $this->assertSame($plaintext, $decrypted);
    }

    /**
     * Comprueba que se puedan cifrar datos binarios.
     */
    public function testEncryptAndDecryptBinaryData(): void
    {
        $plaintext = "\x00\x01\x02\xFF\xFE\x80\x00";

        $encrypted = $this->encryption->encrypt($plaintext);

        $decrypted = $this->encryption->decrypt($encrypted);

        $this->assertSame($plaintext, $decrypted);
    }

    /**
     * Comprueba que se puedan procesar mensajes grandes.
     */
    public function testEncryptAndDecryptLargeMessage(): void
    {
        $plaintext = str_repeat(
            'ShieldPHP secure encryption. ',
            10000
        );

        $encrypted = $this->encryption->encrypt($plaintext);

        $decrypted = $this->encryption->decrypt($encrypted);

        $this->assertSame($plaintext, $decrypted);
    }

    /**
     * Comprueba que dos cifrados del mismo mensaje
     * produzcan resultados diferentes por el nonce aleatorio.
     */
    public function testGeneratesDifferentCiphertextsForSamePlaintext(): void
    {
        $plaintext = 'Same plaintext for encryption';

        $firstEncrypted = $this->encryption->encrypt($plaintext);

        $secondEncrypted = $this->encryption->encrypt($plaintext);

        $this->assertNotSame(
            $firstEncrypted,
            $secondEncrypted
        );

        $this->assertSame(
            $plaintext,
            $this->encryption->decrypt($firstEncrypted)
        );

        $this->assertSame(
            $plaintext,
            $this->encryption->decrypt($secondEncrypted)
        );
    }

    /**
     * Comprueba que el mensaje pueda descifrarse
     * utilizando otra instancia con la misma clave.
     */
    public function testDecryptsWithAnotherInstanceUsingSameKey(): void
    {
        $plaintext = 'Shared encryption key test';

        $firstInstance = $this->createEncryption();
        $secondInstance = $this->createEncryption();

        $encrypted = $firstInstance->encrypt($plaintext);

        $decrypted = $secondInstance->decrypt($encrypted);

        $this->assertSame($plaintext, $decrypted);
    }

    /**
     * Comprueba que una clave diferente no pueda
     * descifrar el mensaje.
     */
    public function testRejectsDifferentEncryptionKey(): void
    {
        $plaintext = 'Confidential data';

        $encrypted = $this->encryption->encrypt($plaintext);

        $anotherEncryption = $this->createEncryption(
            'shieldphp-different-key-material-01'
        );

        $this->expectException(EncryptionException::class);

        $anotherEncryption->decrypt($encrypted);
    }

    /**
     * Comprueba que una modificación del ciphertext
     * sea detectada durante el descifrado.
     */
    public function testRejectsTamperedCiphertext(): void
    {
        $encrypted = $this->encryption->encrypt(
            'Sensitive information'
        );

        $payload = $this->decodePayload($encrypted);

        $ciphertextOffset = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;

        $this->assertGreaterThan(
            $ciphertextOffset,
            strlen($payload)
        );

        $payload[$ciphertextOffset] = chr(
            ord($payload[$ciphertextOffset]) ^ 0x01
        );

        $tampered = $this->encodePayload($payload);

        $this->expectException(EncryptionException::class);

        $this->encryption->decrypt($tampered);
    }

    /**
     * Comprueba que una modificación del nonce
     * impida el descifrado.
     */
    public function testRejectsTamperedNonce(): void
    {
        $encrypted = $this->encryption->encrypt(
            'Sensitive information'
        );

        $payload = $this->decodePayload($encrypted);

        $payload[0] = chr(ord($payload[0]) ^ 0x01);

        $tampered = $this->encodePayload($payload);

        $this->expectException(EncryptionException::class);

        $this->encryption->decrypt($tampered);
    }

    /**
     * Comprueba que se rechace un mensaje cifrado
     * con una versión no soportada.
     */
    public function testRejectsUnsupportedVersion(): void
    {
        $encrypted = $this->encryption->encrypt(
            'Sensitive information'
        );

        $unsupportedVersion = 'shp:v2:' .
            substr($encrypted, strlen(self::PREFIX));

        $this->expectException(EncryptionException::class);

        $this->encryption->decrypt($unsupportedVersion);
    }

    /**
     * Comprueba que se rechace un formato incorrecto.
     */
    public function testRejectsInvalidEncryptedFormat(): void
    {
        $this->expectException(EncryptionException::class);

        $this->encryption->decrypt(
            'invalid-encrypted-message'
        );
    }

    /**
     * Comprueba que se rechace un mensaje vacío
     * que no contiene el encabezado de cifrado.
     */
    public function testRejectsEmptyEncryptedMessage(): void
    {
        $this->expectException(EncryptionException::class);

        $this->encryption->decrypt('');
    }

    /**
     * Comprueba que se rechace una carga Base64 inválida.
     */
    public function testRejectsInvalidBase64Payload(): void
    {
        $encrypted = self::PREFIX . '%%%invalid-base64%%%';

        $this->expectException(EncryptionException::class);

        $this->encryption->decrypt($encrypted);
    }

    /**
     * Comprueba que se rechace una carga binaria
     * demasiado corta para contener nonce y MAC.
     */
    public function testRejectsPayloadTooShort(): void
    {
        $encrypted = $this->encodePayload('short');

        $this->expectException(EncryptionException::class);

        $this->encryption->decrypt($encrypted);
    }

    /**
     * Comprueba que se rechace un mensaje
     * con una carga binaria vacía.
     */
    public function testRejectsEmptyBinaryPayload(): void
    {
        $encrypted = $this->encodePayload('');

        $this->expectException(EncryptionException::class);

        $this->encryption->decrypt($encrypted);
    }

    /**
     * Comprueba que no se pueda construir Encryption
     * con una clave demasiado corta.
     */
    public function testRejectsShortEncryptionKey(): void
    {
        $this->expectException(EncryptionException::class);

        new Encryption('short-key');
    }

    /**
     * Comprueba que no se pueda construir Encryption
     * con una clave demasiado larga.
     */
    public function testRejectsLongEncryptionKey(): void
    {
        $this->expectException(EncryptionException::class);

        new Encryption(str_repeat('A', 64));
    }

    /**
     * Comprueba que una clave de longitud exacta
     * sea aceptada.
     */
    public function testAcceptsCorrectKeyLength(): void
    {
        $key = str_repeat(
            'K',
            SODIUM_CRYPTO_SECRETBOX_KEYBYTES
        );

        $encryption = new Encryption($key);

        $this->assertInstanceOf(Encryption::class, $encryption);
    }

    /**
     * Comprueba que varias operaciones de cifrado
     * y descifrado no interfieran entre sí.
     */
    public function testMultipleEncryptionOperationsRemainIndependent(): void
    {
        $messages = [
            'First secret message',
            'Second secret message',
            'Third secret message',
            'Fourth secret message',
        ];

        $encryptedMessages = [];

        foreach ($messages as $message) {
            $encryptedMessages[] = $this->encryption->encrypt($message);
        }

        foreach ($messages as $index => $message) {
            $this->assertSame(
                $message,
                $this->encryption->decrypt(
                    $encryptedMessages[$index]
                )
            );
        }
    }

    /**
     * Comprueba que los mensajes cifrados sean independientes:
     * un ciphertext no debe descifrar correctamente otro mensaje.
     */
    public function testCiphertextsCannotBeInterchanged(): void
    {
        $firstEncrypted = $this->encryption->encrypt(
            'First confidential message'
        );

        $secondEncrypted = $this->encryption->encrypt(
            'Second confidential message'
        );

        $firstPayload = $this->decodePayload($firstEncrypted);
        $secondPayload = $this->decodePayload($secondEncrypted);

        $this->assertNotSame($firstPayload, $secondPayload);

        $this->assertSame(
            'First confidential message',
            $this->encryption->decrypt($firstEncrypted)
        );

        $this->assertSame(
            'Second confidential message',
            $this->encryption->decrypt($secondEncrypted)
        );
    }
}
