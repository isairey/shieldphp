<?php

declare(strict_types=1);

namespace ShieldPHP\Core\JWT;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonException;

final class JWTClaims
{
    /**
     * Claims registrados que no pueden sobrescribirse
     * mediante claims personalizados.
     *
     * @var list<string>
     */
    private const RESERVED_CLAIMS = [
        'iss',
        'sub',
        'aud',
        'exp',
        'nbf',
        'iat',
        'jti',
    ];

    private readonly string $issuer;

    private readonly string $subject;

    /**
     * @var string|list<string>
     */
    private readonly string|array $audience;

    private readonly DateTimeImmutable $issuedAt;

    private readonly DateTimeImmutable $expiresAt;

    private readonly ?DateTimeImmutable $notBefore;

    private readonly ?string $tokenId;

    /**
     * @var array<string, mixed>
     */
    private readonly array $customClaims;

    /**
     * @param string|list<string> $audience
     * @param array<string, mixed> $customClaims
     */
    public function __construct(
        string $issuer,
        string $subject,
        string|array $audience,
        DateTimeImmutable $issuedAt,
        DateTimeImmutable $expiresAt,
        ?DateTimeImmutable $notBefore = null,
        ?string $tokenId = null,
        array $customClaims = []
    ) {
        $issuer = trim($issuer);
        $subject = trim($subject);

        if ($issuer === '') {
            throw new InvalidArgumentException(
                'El emisor del JWT no puede estar vacío.'
            );
        }

        if ($subject === '') {
            throw new InvalidArgumentException(
                'El identificador del usuario no puede estar vacío.'
            );
        }

        $this->validateAudience($audience);

        if ($expiresAt <= $issuedAt) {
            throw new InvalidArgumentException(
                'La expiración debe ser posterior a la emisión del token.'
            );
        }

        if ($notBefore !== null && $notBefore >= $expiresAt) {
            throw new InvalidArgumentException(
                'La fecha de activación debe ser anterior a la expiración.'
            );
        }

        if ($tokenId !== null) {
            $tokenId = trim($tokenId);

            if ($tokenId === '' || strlen($tokenId) > 255) {
                throw new InvalidArgumentException(
                    'El identificador del token no es válido.'
                );
            }
        }

        $this->validateCustomClaims($customClaims);

        try {
            json_encode($customClaims, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException(
                'Los claims personalizados deben poder codificarse como JSON.',
                0,
                $exception
            );
        }

        $this->issuer = $issuer;
        $this->subject = $subject;
        $this->audience = $audience;
        $this->issuedAt = $issuedAt;
        $this->expiresAt = $expiresAt;
        $this->notBefore = $notBefore;
        $this->tokenId = $tokenId;
        $this->customClaims = $customClaims;
    }

    /**
     * Obtiene el emisor (iss).
     */
    public function getIssuer(): string
    {
        return $this->issuer;
    }

    /**
     * Obtiene el sujeto o usuario (sub).
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * Obtiene la audiencia (aud).
     *
     * @return string|list<string>
     */
    public function getAudience(): string|array
    {
        return $this->audience;
    }

    /**
     * Obtiene la fecha de emisión (iat).
     */
    public function getIssuedAt(): DateTimeImmutable
    {
        return $this->issuedAt;
    }

    /**
     * Obtiene la fecha de expiración (exp).
     */
    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * Obtiene la fecha a partir de la cual el token es válido (nbf).
     */
    public function getNotBefore(): ?DateTimeImmutable
    {
        return $this->notBefore;
    }

    /**
     * Obtiene el identificador único del token (jti).
     */
    public function getTokenId(): ?string
    {
        return $this->tokenId;
    }

    /**
     * Obtiene los claims personalizados.
     *
     * @return array<string, mixed>
     */
    public function getCustomClaims(): array
    {
        return $this->customClaims;
    }

    /**
     * Comprueba si el token ya ha expirado.
     */
    public function isExpired(
        ?DateTimeImmutable $now = null
    ): bool {
        $now ??= new DateTimeImmutable();

        return $now->getTimestamp() >= $this->expiresAt->getTimestamp();
    }

    /**
     * Comprueba si el token todavía no puede utilizarse.
     */
    public function isNotYetValid(
        ?DateTimeImmutable $now = null
    ): bool {
        if ($this->notBefore === null) {
            return false;
        }

        $now ??= new DateTimeImmutable();

        return $now->getTimestamp() < $this->notBefore->getTimestamp();
    }

    /**
     * Convierte los claims a un array compatible con un JWT.
     *
     * Las fechas se representan como NumericDate:
     * segundos desde Unix epoch.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $claims = $this->customClaims;

        $claims['iss'] = $this->issuer;
        $claims['sub'] = $this->subject;
        $claims['aud'] = $this->audience;
        $claims['iat'] = $this->issuedAt->getTimestamp();
        $claims['exp'] = $this->expiresAt->getTimestamp();

        if ($this->notBefore !== null) {
            $claims['nbf'] = $this->notBefore->getTimestamp();
        }

        if ($this->tokenId !== null) {
            $claims['jti'] = $this->tokenId;
        }

        return $claims;
    }

    /**
     * Valida la audiencia.
     *
     * @param string|list<string> $audience
     */
    private function validateAudience(string|array $audience): void
    {
        if (is_string($audience)) {
            if (trim($audience) === '') {
                throw new InvalidArgumentException(
                    'La audiencia del JWT no puede estar vacía.'
                );
            }

            return;
        }

        if (!array_is_list($audience) || $audience === []) {
            throw new InvalidArgumentException(
                'La audiencia debe ser una cadena o una lista no vacía.'
            );
        }

        foreach ($audience as $value) {
            if (!is_string($value) || trim($value) === '') {
                throw new InvalidArgumentException(
                    'Cada audiencia debe ser una cadena no vacía.'
                );
            }
        }
    }

    /**
     * Evita sobrescribir los claims registrados.
     *
     * @param array<string, mixed> $customClaims
     */
    private function validateCustomClaims(array $customClaims): void
    {
        foreach ($customClaims as $name => $value) {
            if (!is_string($name) || trim($name) === '') {
                throw new InvalidArgumentException(
                    'Los nombres de los claims deben ser cadenas no vacías.'
                );
            }

            if (in_array($name, self::RESERVED_CLAIMS, true)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'El claim "%s" está reservado y no puede sobrescribirse.',
                        $name
                    )
                );
            }
        }
    }
}