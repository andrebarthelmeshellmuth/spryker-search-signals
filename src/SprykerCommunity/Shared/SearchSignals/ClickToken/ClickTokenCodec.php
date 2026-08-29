<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Shared\SearchSignals\ClickToken;

use JsonException;

/**
 * Stateless, HMAC-signed encoding of a search-result context (query, sku, rank, timestamp) for the
 * SRP -> PDP click-tracking redirect. Deliberately carries NOTHING about the visitor -- no session id,
 * no customer id, no IP -- so a decoded token can never re-identify the request that produced it. This
 * is what keeps channel 3a (see the search-signals plan) free of personal data: the payload is exactly
 * the search context and nothing else, and it is never persisted anywhere except as the row it produces.
 *
 * Both the encoder (Client-layer, runs at SRP render) and the decoder (Yves-layer, runs at the
 * click-redirect landing route) run entirely server-side and share one secret via project config -- the
 * token itself is visible to the browser (it's a URL parameter), but that's fine: HMAC verification only
 * proves the payload wasn't tampered with, it was never meant to be confidential.
 */
class ClickTokenCodec
{
    /**
     * @var string
     */
    protected const FIELD_QUERY = 'q';

    /**
     * @var string
     */
    protected const FIELD_SKU = 's';

    /**
     * @var string
     */
    protected const FIELD_RANK = 'r';

    /**
     * @var string
     */
    protected const FIELD_STORE = 'st';

    /**
     * @var string
     */
    protected const FIELD_LOCALE = 'l';

    /**
     * @var string
     */
    protected const FIELD_ISSUED_AT = 't';

    /**
     * @param string $secret
     * @param int $maxAgeSeconds How long a token stays valid after issuance -- bounds how long a stale/replayed
     * link can still produce a click event. Not a security control (the payload carries no identity to protect),
     * purely a data-quality one: an old token attributes a click to a search result set that may no longer match
     * what's actually indexed.
     */
    public function __construct(
        protected string $secret,
        protected int $maxAgeSeconds = 3600,
    ) {
    }

    /**
     * @param string $query
     * @param string $abstractSku
     * @param int $rank
     * @param string $storeName
     * @param string $localeName
     * @param int|null $issuedAtTimestamp Injectable only for deterministic tests -- production callers omit it.
     *
     * @return string URL-safe token, `<base64url-payload>.<base64url-hmac>`.
     */
    public function encode(
        string $query,
        string $abstractSku,
        int $rank,
        string $storeName,
        string $localeName,
        ?int $issuedAtTimestamp = null,
    ): string {
        $payload = [
            static::FIELD_QUERY => $query,
            static::FIELD_SKU => $abstractSku,
            static::FIELD_RANK => $rank,
            static::FIELD_STORE => $storeName,
            static::FIELD_LOCALE => $localeName,
            static::FIELD_ISSUED_AT => $issuedAtTimestamp ?? time(),
        ];

        $encodedPayload = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = $this->sign($encodedPayload);

        return $encodedPayload . '.' . $signature;
    }

    /**
     * @param string $token
     *
     * @return \SprykerCommunity\Shared\SearchSignals\ClickToken\ClickTokenPayload|null `null` for a missing
     * separator, a bad signature, malformed JSON, or an expired token -- every failure mode collapses to "not a
     * valid click", never an exception, since this runs on a public, unauthenticated redirect route.
     */
    public function decode(string $token): ?ClickTokenPayload
    {
        $parts = explode('.', $token, 2);

        if (count($parts) !== 2) {
            return null;
        }

        [$encodedPayload, $signature] = $parts;

        if (!hash_equals($this->sign($encodedPayload), $signature)) {
            return null;
        }

        $json = $this->base64UrlDecode($encodedPayload);

        if ($json === null) {
            return null;
        }

        try {
            /** @var array<string, mixed> $payload */
            $payload = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (!$this->isWellFormed($payload)) {
            return null;
        }

        $issuedAt = (int)$payload[static::FIELD_ISSUED_AT];

        if (time() - $issuedAt > $this->maxAgeSeconds) {
            return null;
        }

        return new ClickTokenPayload(
            query: (string)$payload[static::FIELD_QUERY],
            abstractSku: (string)$payload[static::FIELD_SKU],
            rank: (int)$payload[static::FIELD_RANK],
            storeName: (string)$payload[static::FIELD_STORE],
            localeName: (string)$payload[static::FIELD_LOCALE],
            issuedAtTimestamp: $issuedAt,
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function isWellFormed(array $payload): bool
    {
        foreach ([static::FIELD_QUERY, static::FIELD_SKU, static::FIELD_RANK, static::FIELD_STORE, static::FIELD_LOCALE, static::FIELD_ISSUED_AT] as $field) {
            if (!array_key_exists($field, $payload)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $encodedPayload
     */
    protected function sign(string $encodedPayload): string
    {
        return $this->base64UrlEncode(hash_hmac('sha256', $encodedPayload, $this->secret, true));
    }

    /**
     * @param string $value
     */
    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /**
     * @param string $value
     */
    protected function base64UrlDecode(string $value): ?string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
