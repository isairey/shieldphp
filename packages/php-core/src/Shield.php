
<?php

declare(strict_types=1);

namespace ShieldPHP\Core;

use ShieldPHP\Core\Auth\AuthManager;
use ShieldPHP\Core\Auth\LoginService;
use ShieldPHP\Core\Auth\PasswordHasher;
use ShieldPHP\Core\Auth\PasswordPolicy;
use ShieldPHP\Core\Authorization\AccessControl;
use ShieldPHP\Core\Authorization\PermissionManager;
use ShieldPHP\Core\Authorization\RoleManager;
use ShieldPHP\Core\Config\ShieldConfig;
use ShieldPHP\Core\Contracts\ClockInterface;
use ShieldPHP\Core\Contracts\SessionStoreInterface;
use ShieldPHP\Core\Contracts\TokenRevocationStoreInterface;
use ShieldPHP\Core\Contracts\UserProviderInterface;
use ShieldPHP\Core\JWT\JWTConfig;
use ShieldPHP\Core\JWT\JWTManager;
use ShieldPHP\Core\JWT\TokenValidator;
use ShieldPHP\Core\OAuth\OAuthManager;
use ShieldPHP\Core\OAuth\OAuthStateManager;
use ShieldPHP\Core\OAuth\PKCEManager;
use ShieldPHP\Core\Session\SessionConfig;
use ShieldPHP\Core\Session\SessionManager;

final class Shield
{
    /**
     * Versión actual de ShieldPHP.
     */
    private const VERSION = '0.1.0';

    /**
     * Configuración principal de la librería.
     */
    private readonly ShieldConfig $config;

    /**
     * Inicializa ShieldPHP.
     *
     * @param array<string, mixed>|ShieldConfig $config
     */
    public function __construct(
        array|ShieldConfig $config = []
    ) {
        $this->config = $config instanceof ShieldConfig
            ? $config
            : new ShieldConfig($config);
    }

    /**
     * Obtiene la configuración principal.
     */
    public function config(): ShieldConfig
    {
        return $this->config;
    }

    /**
     * Obtiene la versión de ShieldPHP.
     */
    public function version(): string
    {
        return self::VERSION;
    }

    /**
     * Crea un administrador de contraseñas.
     */
    public function createPasswordHasher(
        string|int|null $algorithm = PASSWORD_DEFAULT,
        array $options = []
    ): PasswordHasher {
        return new PasswordHasher(
            algorithm: $algorithm,
            options: $options
        );
    }

    /**
     * Crea una política de contraseñas.
     */
    public function createPasswordPolicy(
        int $minLength = 12,
        int $maxLength = 1024
    ): PasswordPolicy {
        return new PasswordPolicy(
            minLength: $minLength,
            maxLength: $maxLength
        );
    }

    /**
     * Crea el administrador de autenticación.
     */
    public function createAuthManager(
        UserProviderInterface $userProvider,
        ?PasswordHasher $passwordHasher = null
    ): AuthManager {
        return new AuthManager(
            userProvider: $userProvider,
            passwordHasher: $passwordHasher
                ?? $this->createPasswordHasher()
        );
    }

    /**
     * Crea el servicio de inicio de sesión.
     */
    public function createLoginService(
        AuthManager $authManager
    ): LoginService {
        return new LoginService($authManager);
    }

    /**
     * Crea un administrador JWT.
     */
    public function createJWTManager(
        JWTConfig $config
    ): JWTManager {
        return new JWTManager($config);
    }

    /**
     * Crea un validador JWT con revocación opcional.
     */
    public function createTokenValidator(
        JWTManager $jwtManager,
        ?TokenRevocationStoreInterface $revocationStore = null
    ): TokenValidator {
        return new TokenValidator(
            jwtManager: $jwtManager,
            revocationStore: $revocationStore
        );
    }

    /**
     * Crea el administrador OAuth 2.0.
     */
    public function createOAuthManager(
        OAuthStateManager $stateManager,
        PKCEManager $pkceManager
    ): OAuthManager {
        return new OAuthManager(
            stateManager: $stateManager,
            pkceManager: $pkceManager
        );
    }

    /**
     * Crea el administrador de sesiones.
     */
    public function createSessionManager(
        SessionStoreInterface $store,
        SessionConfig $config,
        ?ClockInterface $clock = null
    ): SessionManager {
        return new SessionManager(
            store: $store,
            config: $config,
            clock: $clock
        );
    }

    /**
     * Crea el administrador de control de acceso.
     *
     * @param array<string, list<string>> $rolePermissions
     */
    public function createAccessControl(
        array $rolePermissions = []
    ): AccessControl {
        return new AccessControl($rolePermissions);
    }

    /**
     * Crea un administrador de roles.
     */
    public function createRoleManager(): RoleManager
    {
        return new RoleManager();
    }

    /**
     * Crea un administrador de permisos.
     */
    public function createPermissionManager(): PermissionManager
    {
        return new PermissionManager();
    }
}
