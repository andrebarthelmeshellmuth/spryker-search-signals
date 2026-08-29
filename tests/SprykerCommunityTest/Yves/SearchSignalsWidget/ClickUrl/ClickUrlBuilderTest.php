<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Yves\SearchSignalsWidget\ClickUrl;

use Codeception\Test\Unit;
use SprykerCommunity\Client\SearchSignals\SearchSignalsClientInterface;
use SprykerCommunity\Shared\SearchSignals\ClickToken\ClickTokenCodec;
use SprykerCommunity\Yves\SearchSignalsWidget\ClickUrl\ClickUrlBuilder;

/**
 * @group SprykerCommunityTest
 * @group Yves
 * @group SearchSignalsWidget
 * @group ClickUrl
 * @group ClickUrlBuilderTest
 * @group Portable
 */
class ClickUrlBuilderTest extends Unit
{
    public function testBuildProducesASignedRedirectUrlCarryingTheOriginalDestination(): void
    {
        // Arrange
        $clientMock = $this->createConfiguredMock(SearchSignalsClientInterface::class, [
            'getCurrentStoreName' => 'DE',
            'getCurrentLocaleName' => 'de_DE',
        ]);
        $builder = new ClickUrlBuilder(new ClickTokenCodec('real-secret'), $clientMock);

        // Act
        $url = $builder->build('/de/product/office-chair', 'office chair', 'ABC-123', 2);

        // Assert
        $this->assertStringStartsWith('/search-signals/click?sst=', $url);
        $this->assertStringContainsString('&to=%2Fde%2Fproduct%2Foffice-chair', $url);
    }

    public function testBuildEmbedsATokenThatDecodesBackToTheOriginalContext(): void
    {
        // Arrange
        $clientMock = $this->createConfiguredMock(SearchSignalsClientInterface::class, [
            'getCurrentStoreName' => 'DE',
            'getCurrentLocaleName' => 'de_DE',
        ]);
        $codec = new ClickTokenCodec('real-secret');
        $builder = new ClickUrlBuilder($codec, $clientMock);

        // Act
        $url = $builder->build('/de/product/office-chair', 'office chair', 'ABC-123', 2);
        parse_str((string)parse_url($url, PHP_URL_QUERY), $queryParams);
        $payload = $codec->decode((string)$queryParams['sst']);

        // Assert
        $this->assertNotNull($payload);
        $this->assertSame('office chair', $payload->query);
        $this->assertSame('ABC-123', $payload->abstractSku);
        $this->assertSame(2, $payload->rank);
        $this->assertSame('DE', $payload->storeName);
        $this->assertSame('de_DE', $payload->localeName);
    }

    /**
     * @dataProvider unsafeDestinationProvider
     */
    public function testBuildFallsBackToThePlainDestinationForAnUnsafeUrl(string $unsafeDestination): void
    {
        // Arrange -- client must never even be asked for store/locale once the destination is rejected
        $clientMock = $this->getMockBuilder(SearchSignalsClientInterface::class)->getMock();
        $clientMock->expects($this->never())->method('getCurrentStoreName');
        $builder = new ClickUrlBuilder(new ClickTokenCodec('real-secret'), $clientMock);

        // Act & Assert
        $this->assertSame($unsafeDestination, $builder->build($unsafeDestination, 'chair', 'ABC-123', 0));
    }

    /**
     * @return array<string, array<string>>
     */
    public function unsafeDestinationProvider(): array
    {
        return [
            'protocol-relative' => ['//evil.example.com/phish'],
            'absolute with host' => ['https://evil.example.com/phish'],
            'backslash trick' => ['/ok\\..\\evil'],
            'empty string' => [''],
            'no leading slash' => ['relative/path'],
        ];
    }
}
