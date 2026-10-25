<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Config\ShieldConfig;
use ShieldPHP\Core\Exceptions\ConfigurationException;

final class ShieldConfigTest extends TestCase
{
    public function testLoadsDefaultConfiguration(): void
    {
        $config = new ShieldConfig();

        self::assertSame('ShieldPHP', $config->get('app_name'));
        self::assertSame(900, $config->get('access_token_ttl'));
        self::assertSame(604800, $config->get('refresh_token_ttl'));
    }

    public function testAllowsCustomConfiguration(): void
    {
        $config = new ShieldConfig([
            'app_name' => 'MyApp',
            'access_token_ttl' => 600,
            'refresh_token_ttl' => 3600,
        ]);

        self::assertSame('MyApp', $config->get('app_name'));
        self::assertSame(600, $config->get('access_token_ttl'));
    }

    public function testRejectsUnknownOptions(): void
    {
        $this->expectException(ConfigurationException::class);

        new ShieldConfig(['unknown_option' => true]);
    }

    public function testRejectsInvalidTokenLifetimes(): void
    {
        $this->expectException(ConfigurationException::class);

        new ShieldConfig([
            'access_token_ttl' => 3600,
            'refresh_token_ttl' => 600,
        ]);
    }
}