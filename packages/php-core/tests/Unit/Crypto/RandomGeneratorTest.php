
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Crypto;

use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Crypto\RandomGenerator;

final class RandomGeneratorTest extends TestCase
{
    private RandomGenerator $randomGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->randomGenerator = new RandomGenerator();
    }

    /**
     * Comprueba que se pueda crear RandomGenerator.
     */
    public function testCreatesRandomGeneratorInstance(): void
    {
        $this->assertInstanceOf(
            RandomGenerator::class,
            $this->randomGenerator
        );
    }

    /**
     * Comprueba que bytes() devuelva una cadena.
     */
    public function testBytesReturnsString(): void
    {
        $result = $this->randomGenerator->bytes(32);

        $this->assertIsString($result);
    }

    /**
     * Comprueba que bytes() genere exactamente
     * la cantidad de bytes solicitada.
     */
    public function testBytesReturnsCorrectLength(): void
    {
        $lengths = [1, 8, 16, 32, 64, 128];

        foreach ($lengths as $length) {
            $result = $this->randomGenerator->bytes($length);

            $this->assertSame(
                $length,
                strlen($result)
            );
        }
    }

    /**
     * Comprueba que bytes() produzca valores diferentes
     * en llamadas independientes.
     */
    public function testBytesProducesDifferentValues(): void
    {
        $first = $this->randomGenerator->bytes(32);
        $second = $this->randomGenerator->bytes(32);

        $this->assertNotSame($first, $second);
    }

    /**
     * Comprueba que bytes() pueda generar datos binarios.
     */
    public function testBytesSupportsBinaryData(): void
    {
        $result = $this->randomGenerator->bytes(64);

        $this->assertSame(64, strlen($result));
        $this->assertIsString($result);
    }

    /**
     * Comprueba que bytes() genere una cantidad pequeña de bytes.
     */
    public function testBytesSupportsSingleByteGeneration(): void
    {
        $result = $this->randomGenerator->bytes(1);

        $this->assertSame(1, strlen($result));
    }

    /**
     * Comprueba que hex() devuelva una cadena.
     */
    public function testHexReturnsString(): void
    {
        $result = $this->randomGenerator->hex(32);

        $this->assertIsString($result);
    }

    /**
     * Comprueba que hex() genere una cadena hexadecimal válida.
     */
    public function testHexReturnsValidHexadecimalCharacters(): void
    {
        $result = $this->randomGenerator->hex(32);

        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]+$/i',
            $result
        );
    }

    /**
     * Comprueba que hex() genere dos caracteres
     * por cada byte solicitado.
     */
    public function testHexReturnsCorrectLength(): void
    {
        $lengths = [1, 8, 16, 32, 64];

        foreach ($lengths as $length) {
            $result = $this->randomGenerator->hex($length);

            $this->assertSame(
                $length * 2,
                strlen($result)
            );
        }
    }

    /**
     * Comprueba que una cadena hexadecimal
     * pueda convertirse nuevamente a datos binarios.
     */
    public function testHexCanBeDecodedToOriginalLength(): void
    {
        $result = $this->randomGenerator->hex(32);

        $decoded = hex2bin($result);

        $this->assertNotFalse($decoded);
        $this->assertSame(32, strlen($decoded));
    }

    /**
     * Comprueba que dos cadenas hexadecimales
     * generadas independientemente sean diferentes.
     */
    public function testHexProducesDifferentValues(): void
    {
        $first = $this->randomGenerator->hex(32);
        $second = $this->randomGenerator->hex(32);

        $this->assertNotSame($first, $second);
    }

    /**
     * Comprueba que token() devuelva una cadena.
     */
    public function testTokenReturnsString(): void
    {
        $token = $this->randomGenerator->token(32);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    /**
     * Comprueba que token() utilice caracteres
     * compatibles con Base64URL.
     */
    public function testTokenUsesBase64UrlCharacters(): void
    {
        $token = $this->randomGenerator->token(32);

        $this->assertMatchesRegularExpression(
            '/^[A-Za-z0-9_-]+$/',
            $token
        );
    }

    /**
     * Comprueba que los tokens no contengan
     * caracteres de Base64 tradicional.
     */
    public function testTokenDoesNotContainStandardBase64Characters(): void
    {
        $token = $this->randomGenerator->token(32);

        $this->assertStringNotContainsString('+', $token);
        $this->assertStringNotContainsString('/', $token);
        $this->assertStringNotContainsString('=', $token);
    }

    /**
     * Comprueba que un token Base64URL pueda decodificarse.
     */
    public function testTokenCanBeDecoded(): void
    {
        $token = $this->randomGenerator->token(32);

        $base64 = strtr($token, '-_', '+/');

        $base64 .= str_repeat(
            '=',
            (4 - strlen($base64) % 4) % 4
        );

        $decoded = base64_decode($base64, true);

        $this->assertNotFalse($decoded);
        $this->assertSame(32, strlen($decoded));
    }

    /**
     * Comprueba que dos tokens generados independientemente
     * sean diferentes.
     */
    public function testTokenProducesDifferentValues(): void
    {
        $first = $this->randomGenerator->token(32);
        $second = $this->randomGenerator->token(32);

        $this->assertNotSame($first, $second);
    }

    /**
     * Comprueba que integer() devuelva un entero.
     */
    public function testIntegerReturnsInteger(): void
    {
        $result = $this->randomGenerator->integer(1, 100);

        $this->assertIsInt($result);
    }

    /**
     * Comprueba que integer() respete los límites
     * establecidos.
     */
    public function testIntegerRespectsRange(): void
    {
        $minimum = 10;
        $maximum = 100;

        for ($i = 0; $i < 100; $i++) {
            $result = $this->randomGenerator->integer(
                $minimum,
                $maximum
            );

            $this->assertGreaterThanOrEqual(
                $minimum,
                $result
            );

            $this->assertLessThanOrEqual(
                $maximum,
                $result
            );
        }
    }

    /**
     * Comprueba que integer() admita números negativos.
     */
    public function testIntegerSupportsNegativeRange(): void
    {
        $result = $this->randomGenerator->integer(-100, -1);

        $this->assertGreaterThanOrEqual(-100, $result);
        $this->assertLessThanOrEqual(-1, $result);
    }

    /**
     * Comprueba que integer() admita rangos que incluyan cero.
     */
    public function testIntegerSupportsRangeIncludingZero(): void
    {
        $result = $this->randomGenerator->integer(-50, 50);

        $this->assertGreaterThanOrEqual(-50, $result);
        $this->assertLessThanOrEqual(50, $result);
    }

    /**
     * Comprueba que integer() permita un rango
     * con el mismo valor mínimo y máximo.
     */
    public function testIntegerSupportsEqualMinimumAndMaximum(): void
    {
        $result = $this->randomGenerator->integer(42, 42);

        $this->assertSame(42, $result);
    }

    /**
     * Comprueba que integer() pueda generar números
     * dentro de un rango amplio.
     */
    public function testIntegerSupportsLargeRange(): void
    {
        $minimum = 1;
        $maximum = PHP_INT_MAX;

        $result = $this->randomGenerator->integer(
            $minimum,
            $maximum
        );

        $this->assertGreaterThanOrEqual($minimum, $result);
        $this->assertLessThanOrEqual($maximum, $result);
    }

    /**
     * Comprueba que uuid() devuelva una cadena.
     */
    public function testUuidReturnsString(): void
    {
        $uuid = $this->randomGenerator->uuid();

        $this->assertIsString($uuid);
    }

    /**
     * Comprueba que UUID cumpla el formato estándar.
     */
    public function testUuidHasValidFormat(): void
    {
        $uuid = $this->randomGenerator->uuid();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $uuid
        );
    }

    /**
     * Comprueba que UUID utilice la versión 4.
     */
    public function testUuidUsesVersionFour(): void
    {
        $uuid = $this->randomGenerator->uuid();

        $version = $uuid[14];

        $this->assertSame('4', $version);
    }

    /**
     * Comprueba que UUID tenga el indicador
     * de variante RFC 4122.
     */
    public function testUuidUsesCorrectVariant(): void
    {
        $uuid = $this->randomGenerator->uuid();

        $variant = strtolower($uuid[19]);

        $this->assertContains(
            $variant,
            ['8', '9', 'a', 'b']
        );
    }

    /**
     * Comprueba que los UUID generados sean diferentes.
     */
    public function testUuidProducesDifferentValues(): void
    {
        $firstUuid = $this->randomGenerator->uuid();
        $secondUuid = $this->randomGenerator->uuid();

        $this->assertNotSame($firstUuid, $secondUuid);
    }

    /**
     * Comprueba que puedan generarse múltiples UUID válidos.
     */
    public function testGeneratesMultipleValidUuids(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $uuid = $this->randomGenerator->uuid();

            $this->assertMatchesRegularExpression(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                $uuid
            );
        }
    }

    /**
     * Comprueba que las diferentes operaciones
     * puedan utilizarse consecutivamente.
     */
    public function testDifferentGeneratorsWorkIndependently(): void
    {
        $bytes = $this->randomGenerator->bytes(32);
        $hex = $this->randomGenerator->hex(32);
        $token = $this->randomGenerator->token(32);
        $integer = $this->randomGenerator->integer(1, 1000);
        $uuid = $this->randomGenerator->uuid();

        $this->assertSame(32, strlen($bytes));
        $this->assertSame(64, strlen($hex));
        $this->assertNotEmpty($token);
        $this->assertGreaterThanOrEqual(1, $integer);
        $this->assertLessThanOrEqual(1000, $integer);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f-]{36}$/i',
            $uuid
        );
    }

    /**
     * Comprueba que varias instancias independientes
     * produzcan valores aleatorios diferentes.
     */
    public function testDifferentInstancesGenerateIndependentValues(): void
    {
        $firstGenerator = new RandomGenerator();
        $secondGenerator = new RandomGenerator();

        $firstBytes = $firstGenerator->bytes(32);
        $secondBytes = $secondGenerator->bytes(32);

        $this->assertNotSame($firstBytes, $secondBytes);
    }
}
