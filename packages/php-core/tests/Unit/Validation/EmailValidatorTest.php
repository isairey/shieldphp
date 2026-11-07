
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Validation\EmailValidator;

final class EmailValidatorTest extends TestCase
{
    private EmailValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new EmailValidator();
    }

    /**
     * Comprueba que EmailValidator pueda instanciarse.
     */
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(
            EmailValidator::class,
            $this->validator
        );
    }

    /**
     * Comprueba que acepte una dirección de correo estándar.
     */
    public function testAcceptsStandardEmail(): void
    {
        $this->assertTrue(
            $this->validator->isValid('user@example.com')
        );
    }

    /**
     * Comprueba que acepte direcciones con subdominios.
     */
    public function testAcceptsEmailWithSubdomain(): void
    {
        $this->assertTrue(
            $this->validator->isValid(
                'user@mail.example.com'
            )
        );
    }

    /**
     * Comprueba que acepte direcciones con números.
     */
    public function testAcceptsEmailWithNumbers(): void
    {
        $this->assertTrue(
            $this->validator->isValid(
                'user123@example.com'
            )
        );
    }

    /**
     * Comprueba que acepte puntos en la parte local.
     */
    public function testAcceptsEmailWithDots(): void
    {
        $this->assertTrue(
            $this->validator->isValid(
                'first.last@example.com'
            )
        );
    }

    /**
     * Comprueba que acepte guiones en la parte local.
     */
    public function testAcceptsEmailWithHyphens(): void
    {
        $this->assertTrue(
            $this->validator->isValid(
                'first-last@example.com'
            )
        );
    }

    /**
     * Comprueba que acepte guiones bajos en la parte local.
     */
    public function testAcceptsEmailWithUnderscores(): void
    {
        $this->assertTrue(
            $this->validator->isValid(
                'first_last@example.com'
            )
        );
    }

    /**
     * Comprueba que acepte direcciones con etiquetas mediante +.
     */
    public function testAcceptsEmailWithPlusTag(): void
    {
        $this->assertTrue(
            $this->validator->isValid(
                'user+newsletter@example.com'
            )
        );
    }

    /**
     * Comprueba que acepte dominios con varios niveles.
     */
    public function testAcceptsEmailWithMultipleDomainLevels(): void
    {
        $this->assertTrue(
            $this->validator->isValid(
                'user@mail.company.example.com'
            )
        );
    }

    /**
     * Comprueba que acepte dominios con guiones.
     */
    public function testAcceptsEmailWithHyphenatedDomain(): void
    {
        $this->assertTrue(
            $this->validator->isValid(
                'user@my-company.com'
            )
        );
    }

    /**
     * Comprueba que acepte dominios con extensiones largas.
     */
    public function testAcceptsEmailWithLongTld(): void
    {
        $this->assertTrue(
            $this->validator->isValid(
                'user@example.technology'
            )
        );
    }

    /**
     * Comprueba que acepte direcciones con extensiones de dos letras.
     */
    public function testAcceptsEmailWithShortTld(): void
    {
        $this->assertTrue(
            $this->validator->isValid(
                'user@example.mx'
            )
        );
    }

    /**
     * Comprueba que acepte letras mayúsculas.
     */
    public function testAcceptsEmailWithUppercaseCharacters(): void
    {
        $this->assertTrue(
            $this->validator->isValid(
                'USER@EXAMPLE.COM'
            )
        );
    }

    /**
     * Comprueba que acepte formatos mixtos de mayúsculas y minúsculas.
     */
    public function testAcceptsEmailWithMixedCase(): void
    {
        $this->assertTrue(
            $this->validator->isValid(
                'John.Doe@Example.com'
            )
        );
    }

    /**
     * Comprueba que acepte direcciones con dominios de correo comunes.
     */
    public function testAcceptsCommonEmailDomains(): void
    {
        $emails = [
            'user@gmail.com',
            'user@outlook.com',
            'user@hotmail.com',
            'user@yahoo.com',
            'user@proton.me',
            'user@example.org',
            'user@example.net',
            'user@example.mx',
        ];

        foreach ($emails as $email) {
            $this->assertTrue(
                $this->validator->isValid($email),
                sprintf('La dirección "%s" debería ser válida.', $email)
            );
        }
    }

    /**
     * Comprueba que rechace una cadena vacía.
     */
    public function testRejectsEmptyEmail(): void
    {
        $this->assertFalse(
            $this->validator->isValid('')
        );
    }

    /**
     * Comprueba que rechace una dirección sin arroba.
     */
    public function testRejectsEmailWithoutAtSymbol(): void
    {
        $this->assertFalse(
            $this->validator->isValid('userexample.com')
        );
    }

    /**
     * Comprueba que rechace una dirección sin parte local.
     */
    public function testRejectsEmailWithoutLocalPart(): void
    {
        $this->assertFalse(
            $this->validator->isValid('@example.com')
        );
    }

    /**
     * Comprueba que rechace una dirección sin dominio.
     */
    public function testRejectsEmailWithoutDomain(): void
    {
        $this->assertFalse(
            $this->validator->isValid('user@')
        );
    }

    /**
     * Comprueba que rechace direcciones sin extensión de dominio.
     */
    public function testRejectsEmailWithoutTld(): void
    {
        $this->assertFalse(
            $this->validator->isValid('user@example')
        );
    }

    /**
     * Comprueba que rechace direcciones con múltiples arrobas.
     */
    public function testRejectsMultipleAtSymbols(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'user@@example.com'
            )
        );
    }

    /**
     * Comprueba que rechace espacios en blanco.
     */
    public function testRejectsWhitespace(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'user name@example.com'
            )
        );
    }

    /**
     * Comprueba que rechace espacios al inicio.
     */
    public function testRejectsLeadingWhitespace(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                ' user@example.com'
            )
        );
    }

    /**
     * Comprueba que rechace espacios al final.
     */
    public function testRejectsTrailingWhitespace(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'user@example.com '
            )
        );
    }

    /**
     * Comprueba que rechace dos puntos consecutivos en la parte local.
     */
    public function testRejectsConsecutiveDotsInLocalPart(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'user..name@example.com'
            )
        );
    }

    /**
     * Comprueba que rechace puntos al inicio de la parte local.
     */
    public function testRejectsLeadingDotInLocalPart(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                '.user@example.com'
            )
        );
    }

    /**
     * Comprueba que rechace puntos al final de la parte local.
     */
    public function testRejectsTrailingDotInLocalPart(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'user.@example.com'
            )
        );
    }

    /**
     * Comprueba que rechace dominios que comienzan con un guion.
     */
    public function testRejectsDomainStartingWithHyphen(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'user@-example.com'
            )
        );
    }

    /**
     * Comprueba que rechace dominios que terminan con un guion.
     */
    public function testRejectsDomainEndingWithHyphen(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'user@example-.com'
            )
        );
    }

    /**
     * Comprueba que rechace dominios con puntos consecutivos.
     */
    public function testRejectsConsecutiveDotsInDomain(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'user@example..com'
            )
        );
    }

    /**
     * Comprueba que rechace dominios con guiones bajos.
     */
    public function testRejectsUnderscoresInDomain(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'user@my_company.com'
            )
        );
    }

    /**
     * Comprueba que rechace direcciones con comas.
     */
    public function testRejectsCommas(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'user,name@example.com'
            )
        );
    }

    /**
     * Comprueba que rechace direcciones con punto y coma.
     */
    public function testRejectsSemicolons(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'user;name@example.com'
            )
        );
    }

    /**
     * Comprueba que rechace direcciones con barras.
     */
    public function testRejectsSlashes(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'user/name@example.com'
            )
        );
    }

    /**
     * Comprueba que rechace saltos de línea.
     */
    public function testRejectsNewLines(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                "user@example.com\n"
            )
        );
    }

    /**
     * Comprueba que rechace caracteres de control.
     */
    public function testRejectsControlCharacters(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                "user\x00@example.com"
            )
        );
    }

    /**
     * Comprueba que no acepte un dominio incompleto.
     */
    public function testRejectsIncompleteDomain(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'user@example.'
            )
        );
    }

    /**
     * Comprueba que no acepte una extensión de dominio numérica.
     */
    public function testRejectsNumericTld(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                'user@example.123'
            )
        );
    }

    /**
     * Comprueba que no acepte direcciones con caracteres HTML.
     */
    public function testRejectsHtmlCharacters(): void
    {
        $this->assertFalse(
            $this->validator->isValid(
                '<script>@example.com'
            )
        );
    }

    /**
     * Comprueba que se pueda validar repetidamente
     * sin modificar el comportamiento del validador.
     */
    public function testCanValidateMultipleEmails(): void
    {
        $this->assertTrue(
            $this->validator->isValid('first@example.com')
        );

        $this->assertFalse(
            $this->validator->isValid('invalid-email')
        );

        $this->assertTrue(
            $this->validator->isValid('second@example.org')
        );
    }

    /**
     * Comprueba que la validación no modifique el correo recibido.
     */
    public function testValidationDoesNotModifyInput(): void
    {
        $email = 'User.Name+tag@Example.com';

        $originalEmail = $email;

        $this->validator->isValid($email);

        $this->assertSame(
            $originalEmail,
            $email
        );
    }
}
