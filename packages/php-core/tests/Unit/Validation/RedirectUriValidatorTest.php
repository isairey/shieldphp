
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Validation;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Validation\RedirectUriValidator;

final class RedirectUriValidatorTest extends TestCase
{
    private RedirectUriValidator $validator;

    /**
     * URI permitidas para las pruebas.
     */
    private const ALLOWED_URIS = [
        'https://example.com/oauth/callback',
        'https://app.example.com/auth/google/callback',
        'https://api.example.com/oauth/facebook/callback',
        'http://localhost:8000/oauth/callback',
        'https://example.com/oauth/callback?tenant=acme',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new RedirectUriValidator(
            self::ALLOWED_URIS
        );
    }

    /**
     * Comprueba que RedirectUriValidator pueda instanciarse.
     */
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(
            RedirectUriValidator::class,
            $this->validator
        );
    }

    /*
    |--------------------------------------------------------------------------
    | URI permitidas
    |--------------------------------------------------------------------------
    */

    /**
     * Acepta una URI HTTPS registrada.
     */
    public function testAcceptsRegisteredHttpsUri(): void
    {
        $this->assertTrue(
            $this->validator->isValid(
                'https://example.com/oauth/callback'
            )
        );
    }

    /**
     * Acepta una URI de un subdominio registrado.
     */
    public function testAcceptsRegisteredSubdomainUri(): void
    {
        $this->assertTrue(
            $this->validator->isValid(
                'https://app.example.com/auth/google/callback'
            )
        );
    }

    /**
     * Acepta otra URI registrada para un proveedor OAuth.
     */
    public function testAcceptsRegisteredProviderCallback(): void
    {
        $this->assertTrue(
            $this->validator->isValid(
                'https://api.example.com/oauth/facebook/callback'
            )
        );
    }

    /**
     * Acepta HTTP para localhost cuando está expresamente registrado.
     */
    public function testAcceptsRegisteredLocalhostUri(): void
    {
        $this->assertTrue(
            $this->validator->isValid(
                'http://localhost:8000/oauth/callback'
            )
        );
    }

    /**
     * Acepta una URI con parámetros de consulta
     * cuando coincide exactamente con el registro.
     */
    public function testAcceptsRegisteredUriWithQueryParameters(): void
    {
        $this->assertTrue(
            $this->validator->isValid(
                'https://example.com/oauth/callback?tenant=acme'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | URI no autorizadas
    |--------------------------------------------------------------------------
    */

    /**
     * Rechaza dominios que no se encuentran en la lista permitida.
     */
    public function testRejectsUnregisteredDomain(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'https://attacker.com/oauth/callback'
            )
        );
    }

    /**
     * Rechaza URI HTTP de dominios externos no registrados.
     */
    public function testRejectsUnregisteredHttpUri(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'http://example.com/oauth/callback'
            )
        );
    }

    /**
     * Rechaza un esquema diferente aunque el dominio parezca legítimo.
     */
    public function testRejectsDifferentScheme(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'http://example.com/oauth/callback'
            )
        );
    }

    /**
     * Rechaza dominios que intentan imitar un dominio autorizado.
     */
    public function testRejectsLookalikeDomain(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'https://example.com.attacker.org/oauth/callback'
            )
        );
    }

    /**
     * Rechaza dominios con prefijos engañosos.
     */
    public function testRejectsDomainWithTrustedDomainAsPrefix(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'https://example.com.evil.com/oauth/callback'
            )
        );
    }

    /**
     * Rechaza un puerto diferente al registrado.
     */
    public function testRejectsDifferentPort(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'http://localhost:9000/oauth/callback'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Coincidencia exacta
    |--------------------------------------------------------------------------
    */

    /**
     * Rechaza rutas diferentes dentro del mismo dominio.
     */
    public function testRejectsDifferentPath(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'https://example.com/oauth/other'
            )
        );
    }

    /**
     * Rechaza rutas que agregan una barra final.
     */
    public function testRejectsTrailingSlash(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'https://example.com/oauth/callback/'
            )
        );
    }

    /**
     * Rechaza cambios en la ruta registrada.
     */
    public function testRejectsAdditionalPathSegments(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'https://example.com/oauth/callback/attacker'
            )
        );
    }

    /**
     * Rechaza parámetros de consulta diferentes.
     */
    public function testRejectsDifferentQueryParameters(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'https://example.com/oauth/callback?tenant=evil'
            )
        );
    }

    /**
     * Rechaza parámetros adicionales no registrados.
     */
    public function testRejectsAdditionalQueryParameters(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'https://example.com/oauth/callback?tenant=acme&redirect=evil'
            )
        );
    }

    /**
     * Rechaza variaciones de mayúsculas si no coinciden
     * exactamente con la URI registrada.
     */
    public function testRejectsDifferentUriRepresentation(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'https://EXAMPLE.com/oauth/callback'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Protección contra redirecciones maliciosas
    |--------------------------------------------------------------------------
    */

    /**
     * Rechaza URI con fragmentos.
     */
    public function testRejectsUriWithFragment(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'https://example.com/oauth/callback#attacker'
            )
        );
    }

    /**
     * Rechaza URI que incluyen credenciales.
     */
    public function testRejectsUriWithUserInfo(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'https://user:password@example.com/oauth/callback'
            )
        );
    }

    /**
     * Rechaza rutas relativas.
     */
    public function testRejectsRelativeUri(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                '/oauth/callback'
            )
        );
    }

    /**
     * Rechaza URI relativas al protocolo.
     */
    public function testRejectsProtocolRelativeUri(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                '//attacker.com/oauth/callback'
            )
        );
    }

    /**
     * Rechaza esquemas JavaScript.
     */
    public function testRejectsJavascriptUri(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'javascript:alert(1)'
            )
        );
    }

    /**
     * Rechaza esquemas de datos.
     */
    public function testRejectsDataUri(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'data:text/html,<script>alert(1)</script>'
            )
        );
    }

    /**
     * Rechaza URI con protocolos no permitidos.
     */
    public function testRejectsFtpUri(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'ftp://example.com/oauth/callback'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Entradas inválidas
    |--------------------------------------------------------------------------
    */

    /**
     * Rechaza cadenas vacías.
     */
    public function testRejectsEmptyUri(): void
    {
        $this->assertFalse(
            $this->validator->isValid('')
        );
    }

    /**
     * Rechaza cadenas compuestas únicamente por espacios.
     */
    public function testRejectsWhitespaceUri(): void
    {
        $this->assertFalse(
            $this->validator->isValid('   ')
        );
    }

    /**
     * Rechaza espacios dentro de una URI.
     */
    public function testRejectsUriContainingWhitespace(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'https://example.com/oauth callback'
            )
        );
    }

    /**
     * Rechaza caracteres de control.
     */
    public function testRejectsControlCharacters(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                "https://example.com/oauth/callback\n"
            )
        );
    }

    /**
     * Rechaza URI con un formato incorrecto.
     */
    public function testRejectsMalformedUri(): void
    {
        $invalidUris = [
            'https://',
            'https:///example.com',
            'https://example.com:invalid/callback',
            '://example.com/callback',
            'not-a-uri',
        ];

        foreach ($invalidUris as $uri) {
            $this->assertFalse(
                $this->validator->isValid($uri),
                sprintf('La URI "%s" debería rechazarse.', $uri)
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Validación mediante excepciones
    |--------------------------------------------------------------------------
    */

    /**
     * Comprueba que assertValid acepte URI autorizadas.
     */
    public function testAssertValidAcceptsAllowedUri(): void
    {
        $this->validator->assertValid(
            'https://example.com/oauth/callback'
        );

        $this->assertTrue(true);
    }

    /**
     * Comprueba que assertValid lance una excepción
     * cuando la URI no está autorizada.
     */
    public function testAssertValidRejectsUnregisteredUri(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        $this->validator->assertValid(
            'https://attacker.com/oauth/callback'
        );
    }

    /**
     * Comprueba que una URI maliciosa también genere una excepción.
     */
    public function testAssertValidRejectsMaliciousUri(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        $this->validator->assertValid(
            'javascript:alert(1)'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Consistencia
    |--------------------------------------------------------------------------
    */

    /**
     * Comprueba que las validaciones sean independientes.
     */
    public function testValidationsAreIndependent(): void
    {
        $this->assertTrue(
            $this->validator->isValid(
                'https://example.com/oauth/callback'
            )
        );

        $this->assertFalse(
            $this->validator->isValid(
                'https://attacker.com/oauth/callback'
            )
        );

        $this->assertTrue(
            $this->validator->isValid(
                'https://app.example.com/auth/google/callback'
            )
        );
    }

    /**
     * Comprueba que validar una URI no modifique su valor original.
     */
    public function testValidationDoesNotModifyOriginalUri(): void
    {
        $uri = 'https://example.com/oauth/callback';

        $originalUri = $uri;

        $this->validator->isValid($uri);

        $this->assertSame(
            $originalUri,
            $uri
        );
    }

    /**
     * Comprueba que una URI válida pueda evaluarse repetidamente.
     */
    public function testCanValidateSameUriMultipleTimes(): void
    {
        $uri = 'https://example.com/oauth/callback';

        $this->assertTrue($this->validator->isValid($uri));
        $this->assertTrue($this->validator->isValid($uri));
        $this->assertTrue($this->validator->isValid($uri));
    }
}
