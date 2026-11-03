
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Crypto;

use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Crypto\Signature;

final class SignatureTest extends TestCase
{
    private Signature $signature;

    private string $publicKey;

    private string $secretKey;

    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('sodium')) {
            self::markTestSkipped(
                'The Sodium extension is required for these tests.'
            );
        }

        $this->signature = new Signature();

        $keyPair = $this->generateKeyPair();

        $this->publicKey = $keyPair['public'];
        $this->secretKey = $keyPair['secret'];
    }

    /**
     * Genera un par de claves Ed25519 para las pruebas.
     *
     * @return array{
     *     public: string,
     *     secret: string
     * }
     */
    private function generateKeyPair(): array
    {
        $keyPair = sodium_crypto_sign_keypair();

        $publicKey = sodium_crypto_sign_publickey($keyPair);

        $secretKey = sodium_crypto_sign_secretkey($keyPair);

        return [
            'public' => base64_encode($publicKey),
            'secret' => base64_encode($secretKey),
        ];
    }

    /**
     * Genera una firma para un mensaje.
     */
    private function createSignature(
        string $message,
        ?string $secretKey = null
    ): string {
        return $this->signature->sign(
            $message,
            $secretKey ?? $this->secretKey
        );
    }

    /**
     * Comprueba que se pueda crear Signature.
     */
    public function testCreatesSignatureInstance(): void
    {
        $this->assertInstanceOf(
            Signature::class,
            $this->signature
        );
    }

    /**
     * Comprueba que sign() devuelva una cadena.
     */
    public function testSignReturnsString(): void
    {
        $result = $this->createSignature(
            'Hello ShieldPHP'
        );

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Comprueba que la firma devuelta sea Base64 válido.
     */
    public function testSignReturnsValidBase64Signature(): void
    {
        $result = $this->createSignature(
            'Sensitive message'
        );

        $decoded = base64_decode($result, true);

        $this->assertNotFalse($decoded);
    }

    /**
     * Comprueba que la firma Ed25519 decodificada
     * tenga exactamente 64 bytes.
     */
    public function testSignReturnsCorrectSignatureLength(): void
    {
        $result = $this->createSignature(
            'Hello ShieldPHP'
        );

        $decoded = base64_decode($result, true);

        $this->assertNotFalse($decoded);

        $this->assertSame(
            SODIUM_CRYPTO_SIGN_BYTES,
            strlen($decoded)
        );
    }

    /**
     * Comprueba que verify() acepte una firma válida.
     */
    public function testVerifyAcceptsValidSignature(): void
    {
        $message = 'Hello ShieldPHP';

        $signedMessage = $this->createSignature($message);

        $result = $this->signature->verify(
            $message,
            $signedMessage,
            $this->publicKey
        );

        $this->assertTrue($result);
    }

    /**
     * Comprueba que una modificación del mensaje
     * invalide la firma.
     */
    public function testVerifyRejectsModifiedMessage(): void
    {
        $message = 'Original message';

        $signedMessage = $this->createSignature($message);

        $modifiedMessage = 'Modified message';

        $result = $this->signature->verify(
            $modifiedMessage,
            $signedMessage,
            $this->publicKey
        );

        $this->assertFalse($result);
    }

    /**
     * Comprueba que una firma modificada sea rechazada.
     */
    public function testVerifyRejectsModifiedSignature(): void
    {
        $message = 'Confidential information';

        $signedMessage = $this->createSignature($message);

        $decodedSignature = base64_decode(
            $signedMessage,
            true
        );

        $this->assertNotFalse($decodedSignature);

        $decodedSignature[0] = chr(
            ord($decodedSignature[0]) ^ 0x01
        );

        $modifiedSignature = base64_encode(
            $decodedSignature
        );

        $result = $this->signature->verify(
            $message,
            $modifiedSignature,
            $this->publicKey
        );

        $this->assertFalse($result);
    }

    /**
     * Comprueba que una clave pública diferente
     * no pueda validar la firma.
     */
    public function testVerifyRejectsDifferentPublicKey(): void
    {
        $message = 'Authenticated information';

        $signedMessage = $this->createSignature($message);

        $anotherKeyPair = $this->generateKeyPair();

        $result = $this->signature->verify(
            $message,
            $signedMessage,
            $anotherKeyPair['public']
        );

        $this->assertFalse($result);
    }

    /**
     * Comprueba que una firma generada con otra clave privada
     * no pueda validarse con la clave pública original.
     */
    public function testRejectsSignatureFromAnotherKeyPair(): void
    {
        $message = 'Sensitive data';

        $anotherKeyPair = $this->generateKeyPair();

        $signedMessage = $this->createSignature(
            $message,
            $anotherKeyPair['secret']
        );

        $result = $this->signature->verify(
            $message,
            $signedMessage,
            $this->publicKey
        );

        $this->assertFalse($result);
    }

    /**
     * Comprueba que se puedan firmar mensajes Unicode.
     */
    public function testSupportsUnicodeMessages(): void
    {
        $message = 'Información confidencial: contraseña 🔐 ñ áéíóú';

        $signedMessage = $this->createSignature($message);

        $result = $this->signature->verify(
            $message,
            $signedMessage,
            $this->publicKey
        );

        $this->assertTrue($result);
    }

    /**
     * Comprueba que se puedan firmar datos binarios.
     */
    public function testSupportsBinaryMessages(): void
    {
        $message = "\x00\x01\x02\xFF\xFE\x80";

        $signedMessage = $this->createSignature($message);

        $result = $this->signature->verify(
            $message,
            $signedMessage,
            $this->publicKey
        );

        $this->assertTrue($result);
    }

    /**
     * Comprueba que se pueda firmar un mensaje vacío.
     */
    public function testSupportsEmptyMessages(): void
    {
        $message = '';

        $signedMessage = $this->createSignature($message);

        $result = $this->signature->verify(
            $message,
            $signedMessage,
            $this->publicKey
        );

        $this->assertTrue($result);
    }

    /**
     * Comprueba que se puedan firmar mensajes grandes.
     */
    public function testSupportsLargeMessages(): void
    {
        $message = str_repeat(
            'ShieldPHP digital signature security. ',
            10000
        );

        $signedMessage = $this->createSignature($message);

        $result = $this->signature->verify(
            $message,
            $signedMessage,
            $this->publicKey
        );

        $this->assertTrue($result);
    }

    /**
     * Comprueba que Ed25519 genere firmas deterministas:
     * el mismo mensaje y la misma clave producen
     * la misma firma.
     */
    public function testGeneratesDeterministicSignatures(): void
    {
        $message = 'Deterministic signature test';

        $firstSignature = $this->createSignature($message);

        $secondSignature = $this->createSignature($message);

        $this->assertSame(
            $firstSignature,
            $secondSignature
        );
    }

    /**
     * Comprueba que mensajes diferentes produzcan
     * firmas diferentes con la misma clave.
     */
    public function testDifferentMessagesProduceDifferentSignatures(): void
    {
        $firstSignature = $this->createSignature(
            'First message'
        );

        $secondSignature = $this->createSignature(
            'Second message'
        );

        $this->assertNotSame(
            $firstSignature,
            $secondSignature
        );
    }

    /**
     * Comprueba que una firma pueda verificarse
     * utilizando otra instancia de Signature.
     */
    public function testSignatureCanBeVerifiedByAnotherInstance(): void
    {
        $message = 'Cross-instance verification';

        $signedMessage = $this->createSignature($message);

        $anotherInstance = new Signature();

        $result = $anotherInstance->verify(
            $message,
            $signedMessage,
            $this->publicKey
        );

        $this->assertTrue($result);
    }

    /**
     * Comprueba que una firma Base64 inválida
     * sea rechazada.
     */
    public function testVerifyRejectsInvalidBase64Signature(): void
    {
        $result = $this->signature->verify(
            'Hello ShieldPHP',
            '%%%invalid-base64%%%',
            $this->publicKey
        );

        $this->assertFalse($result);
    }

    /**
     * Comprueba que una firma con longitud incorrecta
     * sea rechazada.
     */
    public function testVerifyRejectsIncorrectSignatureLength(): void
    {
        $invalidSignature = base64_encode(
            random_bytes(32)
        );

        $result = $this->signature->verify(
            'Hello ShieldPHP',
            $invalidSignature,
            $this->publicKey
        );

        $this->assertFalse($result);
    }

    /**
     * Comprueba que una clave pública Base64 inválida
     * sea rechazada durante la verificación.
     */
    public function testVerifyRejectsInvalidPublicKeyBase64(): void
    {
        $signedMessage = $this->createSignature(
            'Hello ShieldPHP'
        );

        $result = $this->signature->verify(
            'Hello ShieldPHP',
            $signedMessage,
            '%%%invalid-base64%%%'
        );

        $this->assertFalse($result);
    }

    /**
     * Comprueba que una clave pública con longitud incorrecta
     * no pueda validar una firma.
     */
    public function testVerifyRejectsIncorrectPublicKeyLength(): void
    {
        $signedMessage = $this->createSignature(
            'Hello ShieldPHP'
        );

        $invalidPublicKey = base64_encode(
            random_bytes(16)
        );

        $result = $this->signature->verify(
            'Hello ShieldPHP',
            $signedMessage,
            $invalidPublicKey
        );

        $this->assertFalse($result);
    }

    /**
     * Comprueba que modificar un solo carácter
     * del mensaje invalide la firma.
     */
    public function testRejectsSingleCharacterModification(): void
    {
        $message = 'Transaction amount: 1000';

        $signedMessage = $this->createSignature($message);

        $modifiedMessage = 'Transaction amount: 1001';

        $result = $this->signature->verify(
            $modifiedMessage,
            $signedMessage,
            $this->publicKey
        );

        $this->assertFalse($result);
    }

    /**
     * Comprueba que una firma no pueda reutilizarse
     * para validar otro mensaje.
     */
    public function testSignatureCannotBeReusedForAnotherMessage(): void
    {
        $firstMessage = 'Authorize transaction A';

        $secondMessage = 'Authorize transaction B';

        $signedMessage = $this->createSignature($firstMessage);

        $this->assertTrue(
            $this->signature->verify(
                $firstMessage,
                $signedMessage,
                $this->publicKey
            )
        );

        $this->assertFalse(
            $this->signature->verify(
                $secondMessage,
                $signedMessage,
                $this->publicKey
            )
        );
    }

    /**
     * Comprueba que varias firmas independientes
     * puedan generarse y verificarse correctamente.
     */
    public function testMultipleSignaturesRemainIndependent(): void
    {
        $messages = [
            'First authenticated message',
            'Second authenticated message',
            'Third authenticated message',
        ];

        $signatures = [];

        foreach ($messages as $message) {
            $signatures[] = $this->createSignature($message);
        }

        foreach ($messages as $index => $message) {
            $this->assertTrue(
                $this->signature->verify(
                    $message,
                    $signatures[$index],
                    $this->publicKey
                )
            );
        }
    }

    /**
     * Comprueba que las claves públicas de un par
     * diferente no validen firmas anteriores.
     */
    public function testDifferentKeyPairsRemainIndependent(): void
    {
        $firstKeyPair = $this->generateKeyPair();
        $secondKeyPair = $this->generateKeyPair();

        $message = 'Independent key pairs';

        $signedMessage = $this->createSignature(
            $message,
            $firstKeyPair['secret']
        );

        $this->assertTrue(
            $this->signature->verify(
                $message,
                $signedMessage,
                $firstKeyPair['public']
            )
        );

        $this->assertFalse(
            $this->signature->verify(
                $message,
                $signedMessage,
                $secondKeyPair['public']
            )
        );
    }
}
