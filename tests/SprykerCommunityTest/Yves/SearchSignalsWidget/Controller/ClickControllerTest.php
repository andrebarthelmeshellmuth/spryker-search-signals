<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Yves\SearchSignalsWidget\Controller;

use Codeception\Test\Unit;
use SprykerCommunity\Client\SearchSignals\SearchSignalsClientInterface;
use SprykerCommunity\Shared\SearchSignals\ClickToken\ClickTokenCodec;
use SprykerCommunity\Shared\SearchSignals\SearchSignalsConfig;
use SprykerCommunity\Yves\SearchSignalsWidget\SearchSignalsWidgetFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * Security-relevant: this is the open-redirect guard on a public, unauthenticated route. `getFactory()`
 * is overridden via {@see TestableClickController} rather than mocked through the real FactoryResolver,
 * which needs a full Yves DI container this Portable suite doesn't have.
 *
 * @group SprykerCommunityTest
 * @group Yves
 * @group SearchSignalsWidget
 * @group Controller
 * @group ClickControllerTest
 * @group Portable
 */
class ClickControllerTest extends Unit
{
    public function testIndexActionRedirectsToTheRealDestinationOnAValidToken(): void
    {
        // Arrange
        $codec = new ClickTokenCodec('real-secret');
        $token = $codec->encode('office chair', 'ABC-123', 2, 'DE', 'de_DE');

        $clientMock = $this->getMockBuilder(SearchSignalsClientInterface::class)->getMock();
        $clientMock->expects($this->once())->method('publishClickEvent')
            ->with('office chair', 'ABC-123', 2, 'DE', 'de_DE');

        $controller = $this->createController($codec, $clientMock);
        $request = new Request([SearchSignalsConfig::CLICK_TOKEN_PARAM => $token, 'to' => '/de/product/office-chair']);

        // Act
        $response = $controller->indexAction($request);

        // Assert
        $this->assertSame('/de/product/office-chair', $response->getTargetUrl());
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testIndexActionStillRedirectsButPublishesNothingForAnInvalidToken(): void
    {
        // Arrange
        $clientMock = $this->getMockBuilder(SearchSignalsClientInterface::class)->getMock();
        $clientMock->expects($this->never())->method('publishClickEvent');

        $controller = $this->createController(new ClickTokenCodec('real-secret'), $clientMock);
        $request = new Request([SearchSignalsConfig::CLICK_TOKEN_PARAM => 'not-a-valid-token', 'to' => '/de/product/office-chair']);

        // Act
        $response = $controller->indexAction($request);

        // Assert
        $this->assertSame('/de/product/office-chair', $response->getTargetUrl());
    }

    public function testIndexActionFallsBackToRootForAMissingDestination(): void
    {
        // Arrange
        $controller = $this->createController(new ClickTokenCodec('real-secret'), $this->getMockBuilder(SearchSignalsClientInterface::class)->getMock());
        $request = new Request();

        // Act
        $response = $controller->indexAction($request);

        // Assert
        $this->assertSame('/', $response->getTargetUrl());
    }

    /**
     * @dataProvider unsafeDestinationProvider
     */
    public function testIndexActionRefusesToRedirectToAnUnsafeDestinationAndFallsBackToRoot(string $unsafeDestination): void
    {
        // Arrange
        $controller = $this->createController(new ClickTokenCodec('real-secret'), $this->getMockBuilder(SearchSignalsClientInterface::class)->getMock());
        $request = new Request(['to' => $unsafeDestination]);

        // Act
        $response = $controller->indexAction($request);

        // Assert
        $this->assertSame('/', $response->getTargetUrl());
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
            'no leading slash' => ['relative/path'],
        ];
    }

    protected function createController(ClickTokenCodec $codec, SearchSignalsClientInterface $client): TestableClickController
    {
        $factoryMock = $this->getMockBuilder(SearchSignalsWidgetFactory::class)
            ->onlyMethods(['createClickTokenCodec', 'getSearchSignalsClient'])
            ->getMock();
        $factoryMock->method('createClickTokenCodec')->willReturn($codec);
        $factoryMock->method('getSearchSignalsClient')->willReturn($client);

        $controller = new TestableClickController();
        $controller->setTestFactory($factoryMock);

        return $controller;
    }
}
