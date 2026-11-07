
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Validation\InputValidator;

final class InputValidatorTest extends TestCase
{
    private InputValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new InputValidator();
    }

    /**
     * Comprueba que InputValidator pueda instanciarse.
     */
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(
            InputValidator::class,
            $this->validator
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Validación de cadenas
    |--------------------------------------------------------------------------
    */

    /**
     * Comprueba que acepte una cadena válida.
     */
    public function testAcceptsValidString(): void
    {
        $this->assertTrue(
            $this->validator->isValidString('Hola mundo')
        );
    }

    /**
     * Comprueba que rechace una cadena vacía.
     */
    public function testRejectsEmptyString(): void
    {
        $this->assertFalse(
            $this->validator->isValidString('')
        );
    }

    /**
     * Comprueba que rechace cadenas compuestas únicamente por espacios.
     */
    public function testRejectsWhitespaceOnlyString(): void
    {
        $this->assertFalse(
            $this->validator->isValidString('     ')
        );
    }

    /**
     * Comprueba que acepte una cadena con números.
     */
    public function testAcceptsStringWithNumbers(): void
    {
        $this->assertTrue(
            $this->validator->isValidString('Usuario123')
        );
    }

    /**
     * Comprueba que acepte caracteres Unicode.
     */
    public function testAcceptsUnicodeString(): void
    {
        $this->assertTrue(
            $this->validator->isValidString('México y España')
        );
    }

    /**
     * Comprueba que acepte caracteres especiales normales.
     */
    public function testAcceptsSpecialCharactersInString(): void
    {
        $this->assertTrue(
            $this->validator->isValidString(
                'Nombre: Isai - Usuario #123'
            )
        );
    }

    /**
     * Comprueba que rechace valores que no sean cadenas.
     */
    public function testRejectsNonStringValues(): void
    {
        $invalidValues = [
            123,
            12.5,
            true,
            false,
            null,
            [],
            new \stdClass(),
        ];

        foreach ($invalidValues as $value) {
            $this->assertFalse(
                $this->validator->isValidString($value)
            );
        }
    }

    /**
     * Comprueba que respete la longitud mínima.
     */
    public function testAcceptsStringWithMinimumLength(): void
    {
        $this->assertTrue(
            $this->validator->isValidString(
                'Usuario',
                5
            )
        );
    }

    /**
     * Comprueba que rechace cadenas menores a la longitud mínima.
     */
    public function testRejectsStringBelowMinimumLength(): void
    {
        $this->assertFalse(
            $this->validator->isValidString(
                'abc',
                5
            )
        );
    }

    /**
     * Comprueba que respete la longitud máxima.
     */
    public function testAcceptsStringWithinMaximumLength(): void
    {
        $this->assertTrue(
            $this->validator->isValidString(
                'Usuario',
                1,
                20
            )
        );
    }

    /**
     * Comprueba que rechace cadenas que superen la longitud máxima.
     */
    public function testRejectsStringAboveMaximumLength(): void
    {
        $this->assertFalse(
            $this->validator->isValidString(
                'Este texto es demasiado largo',
                1,
                10
            )
        );
    }

    /**
     * Comprueba que acepte una cadena en el límite exacto.
     */
    public function testAcceptsStringAtExactMaximumLength(): void
    {
        $this->assertTrue(
            $this->validator->isValidString(
                '12345',
                1,
                5
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Validación de números enteros
    |--------------------------------------------------------------------------
    */

    /**
     * Comprueba que acepte un entero positivo.
     */
    public function testAcceptsPositiveInteger(): void
    {
        $this->assertTrue(
            $this->validator->isValidInteger(123)
        );
    }

    /**
     * Comprueba que acepte el número cero.
     */
    public function testAcceptsZeroInteger(): void
    {
        $this->assertTrue(
            $this->validator->isValidInteger(0)
        );
    }

    /**
     * Comprueba que acepte un entero negativo.
     */
    public function testAcceptsNegativeInteger(): void
    {
        $this->assertTrue(
            $this->validator->isValidInteger(-25)
        );
    }

    /**
     * Comprueba que acepte enteros representados como cadenas.
     */
    public function testAcceptsIntegerString(): void
    {
        $this->assertTrue(
            $this->validator->isValidInteger('12345')
        );
    }

    /**
     * Comprueba que rechace números decimales.
     */
    public function testRejectsDecimalNumbers(): void
    {
        $this->assertFalse(
            $this->validator->isValidInteger(12.5)
        );

        $this->assertFalse(
            $this->validator->isValidInteger('12.5')
        );
    }

    /**
     * Comprueba que rechace cadenas que no sean numéricas.
     */
    public function testRejectsNonNumericStrings(): void
    {
        $this->assertFalse(
            $this->validator->isValidInteger('abc123')
        );

        $this->assertFalse(
            $this->validator->isValidInteger('hello')
        );
    }

    /**
     * Comprueba que rechace valores booleanos como enteros.
     */
    public function testRejectsBooleanAsInteger(): void
    {
        $this->assertFalse(
            $this->validator->isValidInteger(true)
        );

        $this->assertFalse(
            $this->validator->isValidInteger(false)
        );
    }

    /**
     * Comprueba que respete el límite mínimo de un entero.
     */
    public function testAcceptsIntegerAtMinimumBoundary(): void
    {
        $this->assertTrue(
            $this->validator->isValidInteger(
                10,
                10,
                100
            )
        );
    }

    /**
     * Comprueba que rechace enteros menores al mínimo.
     */
    public function testRejectsIntegerBelowMinimum(): void
    {
        $this->assertFalse(
            $this->validator->isValidInteger(
                5,
                10,
                100
            )
        );
    }

    /**
     * Comprueba que respete el límite máximo.
     */
    public function testAcceptsIntegerAtMaximumBoundary(): void
    {
        $this->assertTrue(
            $this->validator->isValidInteger(
                100,
                10,
                100
            )
        );
    }

    /**
     * Comprueba que rechace enteros superiores al máximo.
     */
    public function testRejectsIntegerAboveMaximum(): void
    {
        $this->assertFalse(
            $this->validator->isValidInteger(
                150,
                10,
                100
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Validación de booleanos
    |--------------------------------------------------------------------------
    */

    /**
     * Comprueba que acepte valores booleanos verdaderos.
     */
    public function testAcceptsTrueBooleanValues(): void
    {
        $validValues = [
            true,
            1,
            '1',
            'true',
        ];

        foreach ($validValues as $value) {
            $this->assertTrue(
                $this->validator->isValidBoolean($value)
            );
        }
    }

    /**
     * Comprueba que acepte valores booleanos falsos.
     */
    public function testAcceptsFalseBooleanValues(): void
    {
        $validValues = [
            false,
            0,
            '0',
            'false',
        ];

        foreach ($validValues as $value) {
            $this->assertTrue(
                $this->validator->isValidBoolean($value)
            );
        }
    }

    /**
     * Comprueba que rechace cadenas ambiguas.
     */
    public function testRejectsInvalidBooleanStrings(): void
    {
        $invalidValues = [
            'yes',
            'no',
            'enabled',
            'disabled',
            'on',
            'off',
            'maybe',
        ];

        foreach ($invalidValues as $value) {
            $this->assertFalse(
                $this->validator->isValidBoolean($value)
            );
        }
    }

    /**
     * Comprueba que rechace valores que no sean booleanos válidos.
     */
    public function testRejectsInvalidBooleanTypes(): void
    {
        $invalidValues = [
            null,
            [],
            new \stdClass(),
            2,
            -1,
            1.5,
        ];

        foreach ($invalidValues as $value) {
            $this->assertFalse(
                $this->validator->isValidBoolean($value)
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Validación de correos electrónicos
    |--------------------------------------------------------------------------
    */

    /**
     * Comprueba que acepte un correo válido.
     */
    public function testAcceptsValidEmail(): void
    {
        $this->assertTrue(
            $this->validator->isValidEmail(
                'user@example.com'
            )
        );
    }

    /**
     * Comprueba que acepte un correo con subdominio.
     */
    public function testAcceptsEmailWithSubdomain(): void
    {
        $this->assertTrue(
            $this->validator->isValidEmail(
                'user@mail.example.com'
            )
        );
    }

    /**
     * Comprueba que acepte un correo con etiqueta.
     */
    public function testAcceptsEmailWithPlusTag(): void
    {
        $this->assertTrue(
            $this->validator->isValidEmail(
                'user+test@example.com'
            )
        );
    }

    /**
     * Comprueba que rechace correos inválidos.
     */
    public function testRejectsInvalidEmail(): void
    {
        $invalidEmails = [
            '',
            'user',
            'user@',
            '@example.com',
            'user@example',
            'user@@example.com',
            'user name@example.com',
            'user@example..com',
        ];

        foreach ($invalidEmails as $email) {
            $this->assertFalse(
                $this->validator->isValidEmail($email)
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Validación de URLs
    |--------------------------------------------------------------------------
    */

    /**
     * Comprueba que acepte una URL HTTPS.
     */
    public function testAcceptsHttpsUrl(): void
    {
        $this->assertTrue(
            $this->validator->isValidUrl(
                'https://example.com'
            )
        );
    }

    /**
     * Comprueba que acepte una URL HTTP.
     */
    public function testAcceptsHttpUrl(): void
    {
        $this->assertTrue(
            $this->validator->isValidUrl(
                'http://example.com'
            )
        );
    }

    /**
     * Comprueba que acepte una URL con ruta.
     */
    public function testAcceptsUrlWithPath(): void
    {
        $this->assertTrue(
            $this->validator->isValidUrl(
                'https://example.com/users/profile'
            )
        );
    }

    /**
     * Comprueba que acepte parámetros de consulta.
     */
    public function testAcceptsUrlWithQueryParameters(): void
    {
        $this->assertTrue(
            $this->validator->isValidUrl(
                'https://example.com/search?q=shield'
            )
        );
    }

    /**
     * Comprueba que rechace URLs sin protocolo.
     */
    public function testRejectsUrlWithoutScheme(): void
    {
        $this->assertFalse(
            $this->validator->isValidUrl(
                'example.com'
            )
        );
    }

    /**
     * Comprueba que rechace URLs mal formadas.
     */
    public function testRejectsInvalidUrls(): void
    {
        $invalidUrls = [
            '',
            'not-a-url',
            'https://',
            'http://',
            'https:///example.com',
            'javascript:alert(1)',
            'ftp://example.com',
        ];

        foreach ($invalidUrls as $url) {
            $this->assertFalse(
                $this->validator->isValidUrl($url)
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Comportamiento general
    |--------------------------------------------------------------------------
    */

    /**
     * Comprueba que las validaciones sean independientes.
     */
    public function testValidationsAreIndependent(): void
    {
        $this->assertTrue(
            $this->validator->isValidString('Test')
        );

        $this->assertFalse(
            $this->validator->isValidString('')
        );

        $this->assertTrue(
            $this->validator->isValidInteger(123)
        );

        $this->assertTrue(
            $this->validator->isValidBoolean(true)
        );

        $this->assertTrue(
            $this->validator->isValidEmail(
                'user@example.com'
            )
        );

        $this->assertTrue(
            $this->validator->isValidUrl(
                'https://example.com'
            )
        );
    }

    /**
     * Comprueba que validar una entrada no modifique su valor original.
     */
    public function testValidationDoesNotModifyOriginalInput(): void
    {
        $input = 'User.Name+tag@example.com';

        $originalInput = $input;

        $this->validator->isValidString($input);
        $this->validator->isValidEmail($input);

        $this->assertSame(
            $originalInput,
            $input
        );
    }

    /**
     * Comprueba que un valor válido pueda validarse repetidamente.
     */
    public function testCanValidateSameInputMultipleTimes(): void
    {
        $input = 'user@example.com';

        $this->assertTrue(
            $this->validator->isValidEmail($input)
        );

        $this->assertTrue(
            $this->validator->isValidEmail($input)
        );

        $this->assertTrue(
            $this->validator->isValidEmail($input)
        );
    }
}
