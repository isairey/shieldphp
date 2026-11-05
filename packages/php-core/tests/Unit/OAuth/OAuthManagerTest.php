
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\OAuth;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Exceptions\AuthenticationException;
use ShieldPHP\Core\OAuth\OAuthManager;
use ShieldPHP\Core\OAuth\OAuthProviderInterface;
use ShieldPHP\Core\OAuth\OAuthStateManager;
use ShieldPHP\Core\OAuth\PKCEManager;
use ShieldPHP\Core\OAuth\TokenResponse;

final class OAuthManagerTest extends TestCase
{
    private const REDIRECT_URI =
        'https://app.example.com/oauth/callback';

    private const PROVIDER_NAME = 'google';

    private const STATE = 'secure-oauth-state-123';

    private const CHALLENGE = 'secure-pkce-challenge-456';

    private const VERIFIER = 'secure-pkce-verifier-789';

    private const AUTHORIZATION_CODE = 'authorization-code-123';

    private OAuthStateManager $stateManager;

    private PKCEManager $pkceManager;

    private OAuthManager $oauthManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stateManager = $this->createMock(
            OAuthStateManager::class
        );

        $this->pkceManager = $this->createMock(
            PKCEManager::class
        );

        $this->oauthManager = $this->createOAuthManager();
    }

    /**
     * Crea una instancia de OAuthManager.
     */
    private function createOAuthManager(): OAuthManager
    {
        return new OAuthManager(
            $this->stateManager,
            $this->pkceManager,
            self::REDIRECT_URI
        );
    }

    /**
     * Crea un proveedor OAuth simulado.
     */
    private function createProvider(
        string $name = self::PROVIDER_NAME
    ): OAuthProviderInterface {
        $provider = $this->createMock(
            OAuthProviderInterface::class
        );

        $provider
            ->method('getName')
            ->willReturn($name);

        return $provider;
    }

    /**
     * Crea un TokenResponse para las pruebas.
     */
    private function createTokenResponse(): TokenResponse
    {
        return new TokenResponse(
            'test-access-token',
            'Bearer',
            3600,
            'test-refresh-token',
            'openid email profile',
            null,
            new DateTimeImmutable()
        );
    }

    /**
     * Registra el proveedor simulado.
     */
    private function registerGoogleProvider(): OAuthProviderInterface
    {
        $provider = $this->createProvider();

        $this->oauthManager->registerProvider($provider);

        return $provider;
    }

    /**
     * Comprueba que se pueda crear OAuthManager.
     */
    public function testCreatesOAuthManager(): void
    {
        $manager = $this->createOAuthManager();

        $this->assertInstanceOf(
            OAuthManager::class,
            $manager
        );
    }

    /**
     * Comprueba que se pueda registrar un proveedor OAuth.
     */
    public function testRegistersOAuthProvider(): void
    {
        $provider = $this->createProvider();

        $this->oauthManager->registerProvider($provider);

        $this->assertTrue(true);
    }

    /**
     * Comprueba que se pueda registrar más de un proveedor.
     */
    public function testRegistersMultipleProviders(): void
    {
        $googleProvider = $this->createProvider('google');

        $facebookProvider = $this->createProvider('facebook');

        $this->oauthManager->registerProvider($googleProvider);

        $this->oauthManager->registerProvider($facebookProvider);

        $this->assertTrue(true);
    }

    /**
     * Comprueba que getAuthorizationUrl() devuelva una URL.
     */
    public function testGetAuthorizationUrlReturnsString(): void
    {
        $provider = $this->registerGoogleProvider();

        $this->stateManager
            ->expects($this->once())
            ->method('generateAndStore')
            ->willReturn(self::STATE);

        $this->pkceManager
            ->expects($this->once())
            ->method('createChallenge')
            ->with(self::STATE)
            ->willReturn(self::CHALLENGE);

        $provider
            ->expects($this->once())
            ->method('getAuthorizationUrl')
            ->with(
                self::STATE,
                self::REDIRECT_URI,
                self::CHALLENGE
            )
            ->willReturn(
                'https://accounts.example.com/authorize?state='
                . self::STATE
            );

        $url = $this->oauthManager->getAuthorizationUrl(
            self::PROVIDER_NAME
        );

        $this->assertIsString($url);
        $this->assertNotEmpty($url);
    }

    /**
     * Comprueba que se genere y almacene el state
     * antes de construir la URL de autorización.
     */
    public function testGeneratesAndStoresOAuthState(): void
    {
        $provider = $this->registerGoogleProvider();

        $this->stateManager
            ->expects($this->once())
            ->method('generateAndStore')
            ->willReturn(self::STATE);

        $provider
            ->expects($this->once())
            ->method('getAuthorizationUrl')
            ->with(
                self::STATE,
                self::REDIRECT_URI,
                self::CHALLENGE
            )
            ->willReturn('https://accounts.example.com/authorize');

        $this->pkceManager
            ->method('createChallenge')
            ->willReturn(self::CHALLENGE);

        $this->oauthManager->getAuthorizationUrl(
            self::PROVIDER_NAME
        );
    }

    /**
     * Comprueba que se genere un challenge PKCE.
     */
    public function testCreatesPkceChallenge(): void
    {
        $provider = $this->registerGoogleProvider();

        $this->stateManager
            ->method('generateAndStore')
            ->willReturn(self::STATE);

        $this->pkceManager
            ->expects($this->once())
            ->method('createChallenge')
            ->with(self::STATE)
            ->willReturn(self::CHALLENGE);

        $provider
            ->method('getAuthorizationUrl')
            ->willReturn('https://accounts.example.com/authorize');

        $this->oauthManager->getAuthorizationUrl(
            self::PROVIDER_NAME
        );
    }

    /**
     * Comprueba que el proveedor reciba la URI
     * de redirección configurada.
     */
    public function testUsesConfiguredRedirectUri(): void
    {
        $provider = $this->registerGoogleProvider();

        $this->stateManager
            ->method('generateAndStore')
            ->willReturn(self::STATE);

        $this->pkceManager
            ->method('createChallenge')
            ->willReturn(self::CHALLENGE);

        $provider
            ->expects($this->once())
            ->method('getAuthorizationUrl')
            ->with(
                self::STATE,
                self::REDIRECT_URI,
                self::CHALLENGE
            )
            ->willReturn('https://accounts.example.com/authorize');

        $this->oauthManager->getAuthorizationUrl(
            self::PROVIDER_NAME
        );
    }

    /**
     * Comprueba que se procese un callback válido.
     */
    public function testHandlesSuccessfulOAuthCallback(): void
    {
        $provider = $this->registerGoogleProvider();

        $tokenResponse = $this->createTokenResponse();

        $expectedProfile = [
            'id' => 'google-user-123',
            'email' => 'developer@example.com',
            'name' => 'ShieldPHP Developer',
            'avatar_url' => 'https://images.example.com/avatar.png',
        ];

        $this->stateManager
            ->expects($this->once())
            ->method('consume')
            ->with(self::STATE)
            ->willReturn(true);

        $this->pkceManager
            ->expects($this->once())
            ->method('consumeVerifier')
            ->with(self::STATE)
            ->willReturn(self::VERIFIER);

        $provider
            ->expects($this->once())
            ->method('exchangeCodeForToken')
            ->with(
                self::AUTHORIZATION_CODE,
                self::REDIRECT_URI,
                self::VERIFIER
            )
            ->willReturn($tokenResponse);

        $provider
            ->expects($this->once())
            ->method('fetchUserProfile')
            ->with($tokenResponse)
            ->willReturn($expectedProfile);

        $result = $this->oauthManager->handleCallback(
            self::PROVIDER_NAME,
            [
                'state' => self::STATE,
                'code' => self::AUTHORIZATION_CODE,
            ]
        );

        $this->assertIsArray($result);
        $this->assertSame($expectedProfile, $result);
    }

    /**
     * Comprueba que se rechace un state inválido.
     */
    public function testRejectsInvalidOAuthState(): void
    {
        $provider = $this->registerGoogleProvider();

        $this->stateManager
            ->expects($this->once())
            ->method('consume')
            ->with('invalid-state')
            ->willReturn(false);

        $provider
            ->expects($this->never())
            ->method('exchangeCodeForToken');

        $this->expectException(AuthenticationException::class);

        $this->oauthManager->handleCallback(
            self::PROVIDER_NAME,
            [
                'state' => 'invalid-state',
                'code' => self::AUTHORIZATION_CODE,
            ]
        );
    }

    /**
     * Comprueba que se rechace un callback sin state.
     */
    public function testRejectsCallbackWithoutState(): void
    {
        $provider = $this->registerGoogleProvider();

        $this->stateManager
            ->expects($this->never())
            ->method('consume');

        $provider
            ->expects($this->never())
            ->method('exchangeCodeForToken');

        $this->expectException(AuthenticationException::class);

        $this->oauthManager->handleCallback(
            self::PROVIDER_NAME,
            [
                'code' => self::AUTHORIZATION_CODE,
            ]
        );
    }

    /**
     * Comprueba que se rechace un callback sin code.
     */
    public function testRejectsCallbackWithoutAuthorizationCode(): void
    {
        $provider = $this->registerGoogleProvider();

        $this->stateManager
            ->method('consume')
            ->with(self::STATE)
            ->willReturn(true);

        $this->pkceManager
            ->method('consumeVerifier')
            ->with(self::STATE)
            ->willReturn(self::VERIFIER);

        $provider
            ->expects($this->never())
            ->method('exchangeCodeForToken');

        $this->expectException(AuthenticationException::class);

        $this->oauthManager->handleCallback(
            self::PROVIDER_NAME,
            [
                'state' => self::STATE,
            ]
        );
    }

    /**
     * Comprueba que se rechace un callback con
     * un error devuelto por el proveedor OAuth.
     */
    public function testRejectsProviderAuthorizationError(): void
    {
        $provider = $this->registerGoogleProvider();

        $this->stateManager
            ->method('consume')
            ->with(self::STATE)
            ->willReturn(true);

        $this->pkceManager
            ->method('consumeVerifier')
            ->with(self::STATE)
            ->willReturn(self::VERIFIER);

        $provider
            ->expects($this->never())
            ->method('exchangeCodeForToken');

        $this->expectException(AuthenticationException::class);

        $this->oauthManager->handleCallback(
            self::PROVIDER_NAME,
            [
                'state' => self::STATE,
                'error' => 'access_denied',
                'error_description' => 'User denied authorization.',
            ]
        );
    }

    /**
     * Comprueba que se rechace el callback cuando
     * no se encuentra el code_verifier de PKCE.
     */
    public function testRejectsCallbackWithoutPkceVerifier(): void
    {
        $provider = $this->registerGoogleProvider();

        $this->stateManager
            ->method('consume')
            ->with(self::STATE)
            ->willReturn(true);

        $this->pkceManager
            ->method('consumeVerifier')
            ->with(self::STATE)
            ->willReturn(null);

        $provider
            ->expects($this->never())
            ->method('exchangeCodeForToken');

        $this->expectException(AuthenticationException::class);

        $this->oauthManager->handleCallback(
            self::PROVIDER_NAME,
            [
                'state' => self::STATE,
                'code' => self::AUTHORIZATION_CODE,
            ]
        );
    }

    /**
     * Comprueba que el intercambio del código
     * utilice el code_verifier correcto.
     */
    public function testExchangesAuthorizationCodeWithPkceVerifier(): void
    {
        $provider = $this->registerGoogleProvider();

        $tokenResponse = $this->createTokenResponse();

        $this->stateManager
            ->method('consume')
            ->willReturn(true);

        $this->pkceManager
            ->method('consumeVerifier')
            ->willReturn(self::VERIFIER);

        $provider
            ->expects($this->once())
            ->method('exchangeCodeForToken')
            ->with(
                self::AUTHORIZATION_CODE,
                self::REDIRECT_URI,
                self::VERIFIER
            )
            ->willReturn($tokenResponse);

        $provider
            ->method('fetchUserProfile')
            ->willReturn([
                'id' => 'google-user-123',
                'email' => 'developer@example.com',
                'name' => 'Developer',
                'avatar_url' => null,
            ]);

        $this->oauthManager->handleCallback(
            self::PROVIDER_NAME,
            [
                'state' => self::STATE,
                'code' => self::AUTHORIZATION_CODE,
            ]
        );
    }

    /**
     * Comprueba que se obtenga el perfil
     * después de intercambiar el código.
     */
    public function testFetchesUserProfileAfterTokenExchange(): void
    {
        $provider = $this->registerGoogleProvider();

        $tokenResponse = $this->createTokenResponse();

        $expectedProfile = [
            'id' => 'google-user-456',
            'email' => 'user@example.com',
            'name' => 'Example User',
            'avatar_url' => null,
        ];

        $this->stateManager
            ->method('consume')
            ->willReturn(true);

        $this->pkceManager
            ->method('consumeVerifier')
            ->willReturn(self::VERIFIER);

        $provider
            ->method('exchangeCodeForToken')
            ->willReturn($tokenResponse);

        $provider
            ->expects($this->once())
            ->method('fetchUserProfile')
            ->with($tokenResponse)
            ->willReturn($expectedProfile);

        $result = $this->oauthManager->handleCallback(
            self::PROVIDER_NAME,
            [
                'state' => self::STATE,
                'code' => self::AUTHORIZATION_CODE,
            ]
        );

        $this->assertSame($expectedProfile, $result);
    }

    /**
     * Comprueba que un callback fallido no intercambie
     * códigos de autorización.
     */
    public function testInvalidStatePreventsTokenExchange(): void
    {
        $provider = $this->registerGoogleProvider();

        $this->stateManager
            ->method('consume')
            ->willReturn(false);

        $provider
            ->expects($this->never())
            ->method('exchangeCodeForToken');

        $provider
            ->expects($this->never())
            ->method('fetchUserProfile');

        try {
            $this->oauthManager->handleCallback(
                self::PROVIDER_NAME,
                [
                    'state' => 'attacker-state',
                    'code' => self::AUTHORIZATION_CODE,
                ]
            );

            self::fail('Invalid OAuth state should be rejected.');
        } catch (AuthenticationException) {
            $this->assertTrue(true);
        }
    }

    /**
     * Comprueba que se rechace un proveedor no registrado
     * durante el inicio de autorización.
     */
    public function testRejectsUnknownProviderForAuthorization(): void
    {
        $this->expectException(\Throwable::class);

        $this->oauthManager->getAuthorizationUrl('unknown-provider');
    }

    /**
     * Comprueba que se rechace un proveedor no registrado
     * durante el procesamiento del callback.
     */
    public function testRejectsUnknownProviderForCallback(): void
    {
        $this->stateManager
            ->expects($this->never())
            ->method('consume');

        $this->expectException(\Throwable::class);

        $this->oauthManager->handleCallback(
            'unknown-provider',
            [
                'state' => self::STATE,
                'code' => self::AUTHORIZATION_CODE,
            ]
        );
    }

    /**
     * Comprueba que un estado OAuth consumido
     * no pueda utilizarse nuevamente.
     */
    public function testRejectsReusedOAuthState(): void
    {
        $provider = $this->registerGoogleProvider();

        $tokenResponse = $this->createTokenResponse();

        $this->stateManager
            ->expects($this->exactly(2))
            ->method('consume')
            ->with(self::STATE)
            ->willReturnOnConsecutiveCalls(true, false);

        $this->pkceManager
            ->expects($this->once())
            ->method('consumeVerifier')
            ->with(self::STATE)
            ->willReturn(self::VERIFIER);

        $provider
            ->expects($this->once())
            ->method('exchangeCodeForToken')
            ->willReturn($tokenResponse);

        $provider
            ->expects($this->once())
            ->method('fetchUserProfile')
            ->willReturn([
                'id' => 'google-user-123',
                'email' => 'user@example.com',
                'name' => 'Example User',
                'avatar_url' => null,
            ]);

        $callback = [
            'state' => self::STATE,
            'code' => self::AUTHORIZATION_CODE,
        ];

        $this->oauthManager->handleCallback(
            self::PROVIDER_NAME,
            $callback
        );

        $this->expectException(AuthenticationException::class);

        $this->oauthManager->handleCallback(
            self::PROVIDER_NAME,
            $callback
        );
    }

    /**
     * Comprueba que se registren proveedores
     * sin afectar a las dependencias del administrador.
     */
    public function testProviderRegistrationDoesNotChangeOAuthDependencies(): void
    {
        $provider = $this->createProvider();

        $this->oauthManager->registerProvider($provider);

        $this->assertInstanceOf(
            OAuthStateManager::class,
            $this->stateManager
        );

        $this->assertInstanceOf(
            PKCEManager::class,
            $this->pkceManager
        );
    }

    /**
     * Comprueba que varias solicitudes de autorización
     * utilicen estados y challenges independientes.
     */
    public function testMultipleAuthorizationRequestsRemainIndependent(): void
    {
        $provider = $this->registerGoogleProvider();

        $this->stateManager
            ->expects($this->exactly(2))
            ->method('generateAndStore')
            ->willReturnOnConsecutiveCalls(
                'state-first',
                'state-second'
            );

        $this->pkceManager
            ->expects($this->exactly(2))
            ->method('createChallenge')
            ->willReturnOnConsecutiveCalls(
                'challenge-first',
                'challenge-second'
            );

        $provider
            ->expects($this->exactly(2))
            ->method('getAuthorizationUrl')
            ->willReturnCallback(
                static fn (
                    string $state,
                    string $redirectUri,
                    ?string $challenge
                ): string => sprintf(
                    'https://accounts.example.com/authorize?state=%s&challenge=%s',
                    $state,
                    $challenge
                )
            );

        $firstUrl = $this->oauthManager->getAuthorizationUrl(
            self::PROVIDER_NAME
        );

        $secondUrl = $this->oauthManager->getAuthorizationUrl(
            self::PROVIDER_NAME
        );

        $this->assertNotSame($firstUrl, $secondUrl);

        $this->assertStringContainsString(
            'state-first',
            $firstUrl
        );

        $this->assertStringContainsString(
            'state-second',
            $secondUrl
        );
    }
}
