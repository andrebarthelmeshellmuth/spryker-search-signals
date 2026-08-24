<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Shared\SearchSignals\ClickToken;

use Codeception\Test\Unit;
use SprykerCommunity\Shared\SearchSignals\ClickToken\ClickTokenCodec;

/**
 * @group SprykerCommunityTest
 * @group Shared
 * @group SearchSignals
 * @group ClickToken
 * @group ClickTokenCodecTest
 * @group Portable
 */
class ClickTokenCodecTest extends Unit
{
    public function testDecodeRoundTripsExactlyWhatWasEncoded(): void
    {
        // Arrange
        $codec = new ClickTokenCodec('real-secret');
        $issuedAt = time();
        $token = $codec->encode('office chair', 'ABC-123', 2, 'DE', 'de_DE', issuedAtTimestamp: $issuedAt);

        // Act
        $payload = $codec->decode($token);

        // Assert
        $this->assertNotNull($payload);
        $this->assertSame('office chair', $payload->query);
        $this->assertSame('ABC-123', $payload->abstractSku);
        $this->assertSame(2, $payload->rank);
        $this->assertSame('DE', $payload->storeName);
        $this->assertSame('de_DE', $payload->localeName);
        $this->assertSame($issuedAt, $payload->issuedAtTimestamp);
    }

    public function testDecodeRejectsATokenSignedWithADifferentSecret(): void
    {
        // Arrange
        $token = (new ClickTokenCodec('secret-a'))->encode('chair', 'ABC-123', 0, 'DE', 'de_DE');

        // Act & Assert
        $this->assertNull((new ClickTokenCodec('secret-b'))->decode($token));
    }

    public function testDecodeRejectsATamperedPayload(): void
    {
        // Arrange
        $codec = new ClickTokenCodec('real-secret');
        $token = $codec->encode('chair', 'ABC-123', 0, 'DE', 'de_DE');
        [$payload, $signature] = explode('.', $token, 2);

        // Act -- flip the payload but keep the original (now-mismatched) signature
        $tamperedToken = $payload . 'x.' . $signature;

        // Assert
        $this->assertNull($codec->decode($tamperedToken));
    }

    public function testDecodeRejectsAMissingSeparator(): void
    {
        $this->assertNull((new ClickTokenCodec('real-secret'))->decode('not-a-valid-token'));
    }

    public function testDecodeRejectsEmptyString(): void
    {
        $this->assertNull((new ClickTokenCodec('real-secret'))->decode(''));
    }

    public function testDecodeRejectsAnExpiredToken(): void
    {
        // Arrange -- issued 2 hours ago, max age 1 hour
        $codec = new ClickTokenCodec('real-secret', maxAgeSeconds: 3600);
        $token = $codec->encode('chair', 'ABC-123', 0, 'DE', 'de_DE', issuedAtTimestamp: time() - 7200);

        // Act & Assert
        $this->assertNull($codec->decode($token));
    }

    public function testDecodeAcceptsATokenJustInsideTheMaxAgeWindow(): void
    {
        // Arrange
        $codec = new ClickTokenCodec('real-secret', maxAgeSeconds: 3600);
        $token = $codec->encode('chair', 'ABC-123', 0, 'DE', 'de_DE', issuedAtTimestamp: time() - 3500);

        // Act & Assert
        $this->assertNotNull($codec->decode($token));
    }

    public function testDecodeRejectsPayloadMissingARequiredField(): void
    {
        // Arrange -- hand-craft a signed token whose JSON payload is missing the "rank" field entirely
        $codec = new ClickTokenCodec('real-secret');
        $incompletePayload = ['q' => 'chair', 's' => 'ABC-123', 'st' => 'DE', 'l' => 'de_DE', 't' => time()];
        $encodedPayload = rtrim(strtr(base64_encode((string)json_encode($incompletePayload)), '+/', '-_'), '=');
        $signature = rtrim(strtr(base64_encode(hash_hmac('sha256', $encodedPayload, 'real-secret', true)), '+/', '-_'), '=');

        // Act & Assert
        $this->assertNull($codec->decode($encodedPayload . '.' . $signature));
    }

    public function testEncodeProducesAUrlSafeToken(): void
    {
        // Arrange
        $codec = new ClickTokenCodec('real-secret');

        // Act
        $token = $codec->encode('a query with spaces & special/chars', 'ABC-123', 0, 'DE', 'de_DE');

        // Assert
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]+$/', $token);
    }
}
