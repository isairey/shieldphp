
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Auth;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Auth\LogoutService;
use ShieldPHP\Core\JWT\TokenRevocationStoreInterface;
use ShieldPHP\Core\Session\SessionManager;

final class LogoutServiceTest extends TestCase
{
    private SessionManager $sessionManager;

    private TokenRevocationStoreInterface $revocationStore;

    private LogoutService $logoutService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sessionManager = $this->createMock(
            SessionManager::class
        );

        $this->revocationStore = $this->createMock(
            TokenRevocationStoreInterface::class
        );

        $this->logoutService = new LogoutService(
            $this->sessionManager,
            $this->revocationStore
        );
    }

    /**
     * Comprueba que logout() cierre la sesión actual.
     */
    public function testLogoutDestroysSession(): void
    {
        $this->sessionManager
            ->expects($this->once())
            ->method('destroy');

        $this->logoutService->logout(null, null);
    }

    /**
     * Comprueba que logout() revoque un token cuando
     * recibe un identificador y una fecha de expiración.
     */
    public function testLogoutRevokesToken(): void
    {
        $tokenId = 'token-123';

        $expiresAt = new DateTimeImmutable(
            '2026-11-01 12:00:00 UTC'
        );

        $this->revocationStore
            ->expects($this->once())
            ->method('revoke')
            ->with($tokenId, $expiresAt);

        $this->logoutService->logout(
            $tokenId,
            $expiresAt
        );
    }

    /**
     * Comprueba que se cierre la sesión y se revoque
     * el token durante el mismo proceso de logout.
     */
    public function testLogoutDestroysSessionAndRevokesToken(): void
    {
        $tokenId = 'token-456';

        $expiresAt = new DateTimeImmutable(
            '2026-11-02 15:30:00 UTC'
        );

        $this->sessionManager
            ->expects($this->once())
            ->method('destroy');

        $this->revocationStore
            ->expects($this->once())
            ->method('revoke')
            ->with($tokenId, $expiresAt);

        $this->logoutService->logout(
            $tokenId,
            $expiresAt
        );
    }

    /**
     * Comprueba que no se intente revocar un token
     * cuando no se proporciona su identificador.
     */
    public function testLogoutDoesNotRevokeTokenWithoutTokenId(): void
    {
        $this->revocationStore
            ->expects($this->never())
            ->method('revoke');

        $this->logoutService->logout(
            null,
            new DateTimeImmutable('2026-11-01 12:00:00 UTC')
        );
    }

    /**
     * Comprueba que no se intente revocar un token
     * cuando no se proporciona su expiración.
     */
    public function testLogoutDoesNotRevokeTokenWithoutExpiration(): void
    {
        $this->revocationStore
            ->expects($this->never())
            ->method('revoke');

        $this->logoutService->logout(
            'token-789',
            null
        );
    }

    /**
     * Comprueba que el servicio pueda cerrar la sesión
     * sin disponer de un almacenamiento de revocación.
     */
    public function testLogoutWorksWithoutRevocationStore(): void
    {
        $sessionManager = $this->createMock(
            SessionManager::class
        );

        $sessionManager
            ->expects($this->once())
            ->method('destroy');

        $service = new LogoutService(
            $sessionManager,
            null
        );

        $service->logout(null, null);
    }

    /**
     * Comprueba que el servicio pueda revocar un token
     * sin disponer de un administrador de sesiones.
     */
    public function testLogoutWorksWithoutSessionManager(): void
    {
        $revocationStore = $this->createMock(
            TokenRevocationStoreInterface::class
        );

        $tokenId = 'token-999';

        $expiresAt = new DateTimeImmutable(
            '2026-11-05 18:00:00 UTC'
        );

        $revocationStore
            ->expects($this->once())
            ->method('revoke')
            ->with($tokenId, $expiresAt);

        $service = new LogoutService(
            null,
            $revocationStore
        );

        $service->logout($tokenId, $expiresAt);
    }

    /**
     * Comprueba que el servicio pueda ejecutarse
     * sin administrador de sesiones ni almacenamiento.
     */
    public function testLogoutWorksWithoutDependencies(): void
    {
        $service = new LogoutService(
            null,
            null
        );

        $service->logout(null, null);

        $this->assertTrue(true);
    }

    /**
     * Comprueba que varios cierres de sesión
     * generen operaciones independientes.
     */
    public function testMultipleLogoutCallsAreIndependent(): void
    {
        $this->sessionManager
            ->expects($this->exactly(2))
            ->method('destroy');

        $this->revocationStore
            ->expects($this->exactly(2))
            ->method('revoke');

        $firstExpiration = new DateTimeImmutable(
            '2026-11-01 12:00:00 UTC'
        );

        $secondExpiration = new DateTimeImmutable(
            '2026-11-02 12:00:00 UTC'
        );

        $this->logoutService->logout(
            'token-first',
            $firstExpiration
        );

        $this->logoutService->logout(
            'token-second',
            $secondExpiration
        );
    }
}
