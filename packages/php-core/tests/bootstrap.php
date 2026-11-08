
<?php

declare(strict_types=1);

/**
 * Bootstrap de pruebas unitarias de ShieldPHP.
 *
 * Responsabilidades:
 * - Cargar el autoloader de Composer.
 * - Configurar el entorno de pruebas.
 * - Establecer UTC como zona horaria.
 * - Registrar el autoload de las clases auxiliares de pruebas.
 * - Habilitar los errores y excepciones.
 */

use RuntimeException;

$projectRoot = dirname(__DIR__, 2);

/*
|--------------------------------------------------------------------------
| Verificar e incluir Composer
|--------------------------------------------------------------------------
*/

$autoloadFile = $projectRoot . '/vendor/autoload.php';

if (!is_file($autoloadFile)) {
    throw new RuntimeException(
        'No se encontró vendor/autoload.php. Ejecuta composer install.'
    );
}

require_once $autoloadFile;

/*
|--------------------------------------------------------------------------
| Configuración del entorno
|--------------------------------------------------------------------------
*/

// Utilizar una zona horaria consistente en todas las pruebas.
date_default_timezone_set('UTC');

// Habilitar todos los niveles de errores.
error_reporting(E_ALL);

// Mostrar errores durante las pruebas.
ini_set('display_errors', '1');

/*
|--------------------------------------------------------------------------
| Autoload de clases auxiliares de pruebas
|--------------------------------------------------------------------------
*/

spl_autoload_register(
    static function (string $class): void {
        $prefix = 'ShieldPHP\\Core\\Tests\\Unit\\';

        if (!str_starts_with($class, $prefix)) {
            return;
        }

        $relativeClass = substr(
            $class,
            strlen($prefix)
        );

        $file = __DIR__
            . DIRECTORY_SEPARATOR
            . str_replace(
                '\\',
                DIRECTORY_SEPARATOR,
                $relativeClass
            )
            . '.php';

        if (is_file($file)) {
            require_once $file;
        }
    }
);

/*
|--------------------------------------------------------------------------
| Verificación de requisitos básicos
|--------------------------------------------------------------------------
*/

if (PHP_VERSION_ID < 80200) {
    throw new RuntimeException(
        'ShieldPHP requiere PHP 8.2 o superior para ejecutar sus pruebas.'
    );
}

/*
|--------------------------------------------------------------------------
| Comprobar extensiones necesarias
|--------------------------------------------------------------------------
*/

$requiredExtensions = [
    'json',
    'openssl',
    'sodium',
];

foreach ($requiredExtensions as $extension) {
    if (!extension_loaded($extension)) {
        throw new RuntimeException(
            sprintf(
                'La extensión PHP "%s" es necesaria para ejecutar las pruebas.',
                $extension
            )
        );
    }
}
