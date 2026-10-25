<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Contracts;

use ShieldPHP\Core\Exceptions\EncryptionException;

interface EncryptionInterface
{
    /**
     * Cifra un texto y devuelve el contenido cifrado.
     *
     * El resultado debe incluir o permitir recuperar los datos
     * necesarios para descifrarlo, como el nonce y la versión
     * del formato utilizado.
     *
     * @throws EncryptionException Si ocurre un error durante el cifrado.
     */
    public function encrypt(string $plaintext): string;

    /**
     * Descifra un contenido generado por encrypt().
     *
     * Debe verificar la autenticidad e integridad del contenido
     * antes de devolver el texto original.
     *
     * @throws EncryptionException Si el contenido es inválido,
     *                             fue alterado o no puede descifrarse.
     */
    public function decrypt(string $ciphertext): string;
}