
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Auth;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Auth\PasswordPolicy;

final class PasswordPolicyTest extends TestCase
{
    private PasswordPolicy $passwordPolicy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordPolicy = new PasswordPolicy();
    }

    /**
     * Comprueba que se pueda crear una política con valores predeterminados.
     */
    public function testCreatesPasswordPolicyWithDefaultConfiguration(): void
    {
        $policy = new PasswordPolicy();

        $this->assertInstanceOf(PasswordPolicy::class, $policy);
    }

    /**
     * Comprueba que una contraseña con la longitud mínima
     * predeterminada sea válida.
     */
    public function testAcceptsPasswordWithDefaultMinimumLength(): void
    {
        $password = 'abcdefghijkl';

        $this->assertTrue(
            $this->passwordPolicy->isValid($password)
        );
    }

    /**
     * Comprueba que una contraseña inferior a la longitud
     * mínima predeterminada sea rechazada.
     */
    public function testRejectsPasswordBelowDefaultMinimumLength(): void
    {
        $password = 'abcdefghijk';

        $this->assertFalse(
            $this->passwordPolicy->isValid($password)
        );
    }

    /**
     * Comprueba que una contraseña con la longitud
     * máxima predeterminada sea aceptada.
     */
    public function testAcceptsPasswordWithDefaultMaximumLength(): void
    {
        $password = str_repeat('a', 1024);

        $this->assertTrue(
            $this->passwordPolicy->isValid($password)
        );
    }

    /**
     * Comprueba que una contraseña superior a la longitud
     * máxima predeterminada sea rechazada.
     */
    public function testRejectsPasswordAboveDefaultMaximumLength(): void
    {
        $password = str_repeat('a', 1025);

        $this->assertFalse(
            $this->passwordPolicy->isValid($password)
        );
    }

    /**
     * Comprueba que se pueda configurar una longitud mínima
     * y máxima personalizada.
     */
    public function testSupportsCustomLengthConfiguration(): void
    {
        $policy = new PasswordPolicy(8, 64);

        $password = 'Secure123';

        $this->assertTrue($policy->isValid($password));
    }

    /**
     * Comprueba que se respete la longitud mínima personalizada.
     */
    public function testRejectsPasswordBelowCustomMinimumLength(): void
    {
        $policy = new PasswordPolicy(8, 64);

        $password = '1234567';

        $this->assertFalse($policy->isValid($password));
    }

    /**
     * Comprueba que se respete la longitud máxima personalizada.
     */
    public function testRejectsPasswordAboveCustomMaximumLength(): void
    {
        $policy = new PasswordPolicy(8, 64);

        $password = str_repeat('a', 65);

        $this->assertFalse($policy->isValid($password));
    }

    /**
     * Comprueba que una contraseña situada exactamente
     * en el límite mínimo personalizado sea válida.
     */
    public function testAcceptsPasswordAtCustomMinimumBoundary(): void
    {
        $policy = new PasswordPolicy(8, 64);

        $password = str_repeat('a', 8);

        $this->assertTrue($policy->isValid($password));
    }

    /**
     * Comprueba que una contraseña situada exactamente
     * en el límite máximo personalizado sea válida.
     */
    public function testAcceptsPasswordAtCustomMaximumBoundary(): void
    {
        $policy = new PasswordPolicy(8, 64);

        $password = str_repeat('a', 64);

        $this->assertTrue($policy->isValid($password));
    }

    /**
     * Comprueba que validate() no devuelva errores
     * cuando la contraseña cumple la política.
     */
    public function testValidateReturnsNoErrorsForValidPassword(): void
    {
        $errors = $this->passwordPolicy->validate(
            'SecurePassword123!'
        );

        $this->assertIsArray($errors);
        $this->assertEmpty($errors);
    }

    /**
     * Comprueba que validate() devuelva errores
     * cuando la contraseña es demasiado corta.
     */
    public function testValidateReturnsErrorsForShortPassword(): void
    {
        $errors = $this->passwordPolicy->validate('short');

        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
    }

    /**
     * Comprueba que validate() devuelva errores
     * cuando la contraseña supera el límite máximo.
     */
    public function testValidateReturnsErrorsForLongPassword(): void
    {
        $password = str_repeat('a', 1025);

        $errors = $this->passwordPolicy->validate($password);

        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
    }

    /**
     * Comprueba que assertValid() no lance excepciones
     * para contraseñas válidas.
     */
    public function testAssertValidAcceptsValidPassword(): void
    {
        $this->expectNotToPerformAssertions();

        $this->passwordPolicy->assertValid(
            'SecurePassword123!'
        );
    }

    /**
     * Comprueba que assertValid() lance una excepción
     * cuando la contraseña no cumple la política.
     */
    public function testAssertValidRejectsShortPassword(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->passwordPolicy->assertValid('short');
    }

    /**
     * Comprueba que assertValid() lance una excepción
     * cuando se supera la longitud máxima.
     */
    public function testAssertValidRejectsLongPassword(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->passwordPolicy->assertValid(
            str_repeat('a', 1025)
        );
    }

    /**
     * Comprueba que una contraseña vacía sea rechazada.
     */
    public function testRejectsEmptyPassword(): void
    {
        $this->assertFalse(
            $this->passwordPolicy->isValid('')
        );
    }

    /**
     * Comprueba que una contraseña formada únicamente
     * por letras pueda ser válida si cumple la longitud.
     *
     * La política no exige reglas artificiales de composición.
     */
    public function testDoesNotRequireSpecialCharactersOrNumbers(): void
    {
        $password = 'abcdefghijkl';

        $this->assertTrue(
            $this->passwordPolicy->isValid($password)
        );
    }

    /**
     * Comprueba que una contraseña numérica pueda ser válida
     * si cumple la longitud requerida.
     */
    public function testDoesNotRequireUppercaseLetters(): void
    {
        $password = '123456789012';

        $this->assertTrue(
            $this->passwordPolicy->isValid($password)
        );
    }

    /**
     * Comprueba que varias validaciones no alteren
     * el comportamiento de la política.
     */
    public function testMultipleValidationsRemainIndependent(): void
    {
        $validPassword = 'SecurePassword123!';
        $invalidPassword = 'short';

        $this->assertTrue(
            $this->passwordPolicy->isValid($validPassword)
        );

        $this->assertFalse(
            $this->passwordPolicy->isValid($invalidPassword)
        );

        $this->assertTrue(
            $this->passwordPolicy->isValid($validPassword)
        );
    }
}
