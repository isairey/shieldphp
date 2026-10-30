
<?php

declare(strict_types=1);

namespace ShieldPHP\Core;

use ShieldPHP\Core\Config\ShieldConfig;

/**
 * Fachada de compatibilidad con el nombre anterior QuickAuth.
 *
 * @deprecated Utiliza Shield en los proyectos nuevos.
 */
final class QuickAuth
{
    private readonly Shield $shield;

    /**
     * Inicializa QuickAuth con la configuración proporcionada.
     *
     * @param array<string, mixed>|ShieldConfig $config
     */
    public function __construct(
        array|ShieldConfig $config = []
    ) {
        $this->shield = new Shield($config);
    }

    /**
     * Obtiene la configuración de ShieldPHP.
     */
    public function config(): ShieldConfig
    {
        return $this->shield->config();
    }

    /**
     * Obtiene la versión de ShieldPHP.
     */
    public function version(): string
    {
        return $this->shield->version();
    }

    /**
     * Obtiene la instancia principal de ShieldPHP.
     */
    public function getShield(): Shield
    {
        return $this->shield;
    }
}
