<?php

declare(strict_types=1);

namespace ShieldPHP\Core;

use ShieldPHP\Core\Config\ShieldConfig;

final class Shield
{
    private readonly ShieldConfig $config;

    public function __construct(array|ShieldConfig $config = [])
    {
        $this->config = $config instanceof ShieldConfig
            ? $config
            : new ShieldConfig($config);
    }

    public function config(): ShieldConfig
    {
        return $this->config;
    }

    public function version(): string
    {
        return '0.1.0';
    }
}