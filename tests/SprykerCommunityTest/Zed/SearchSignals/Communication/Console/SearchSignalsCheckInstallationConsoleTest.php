<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchSignals\Communication\Console;

use Codeception\Test\Unit;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Covers only the three checks this console can run without a live Zed container (Locator/DI) --
 * {@see \SprykerCommunity\Zed\SearchSignals\Communication\Console\SearchSignalsCheckInstallationConsole::checkStorageKv()}
 * and {@see checkSchema()} both need `getFactory()`/`class_exists()` against a real host shop and are
 * therefore NOT exercised here; they were live-verified by hand instead (see the package's own session
 * notes) -- run `console search-signals:check-installation` for real coverage of those two.
 *
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchSignals
 * @group Communication
 * @group Console
 * @group SearchSignalsCheckInstallationConsoleTest
 * @group Portable
 */
class SearchSignalsCheckInstallationConsoleTest extends Unit
{
    protected function _after(): void
    {
        putenv('SEARCH_SIGNALS_CLICK_TOKEN_SECRET');
    }

    public function testCheckCoreNamespaceReportsSuccessWhenSprykerCommunityIsRegistered(): void
    {
        // Arrange -- the standalone test harness's own config (tests/_ci-standalone/config/Shared/config_default.php)
        // already registers SprykerCommunity, same as any real project with a spryker-community/* package installed.
        $console = new TestableSearchSignalsCheckInstallationConsole();
        $output = new BufferedOutput();

        // Act
        $console->publicCheckCoreNamespace($output);

        // Assert
        $this->assertSame([], $console->getFailures());
        $this->assertStringContainsString('core namespace "SprykerCommunity" is registered', $output->fetch());
    }

    public function testCheckPluginClassesReportsSuccessForBothRealShippedClasses(): void
    {
        // Arrange
        $console = new TestableSearchSignalsCheckInstallationConsole();
        $output = new BufferedOutput();

        // Act
        $console->publicCheckPluginClasses($output);

        // Assert
        $this->assertSame([], $console->getFailures());
        $printed = $output->fetch();
        $this->assertStringContainsString('cart-item-expander plugin', $printed);
        $this->assertStringContainsString('order-post-save plugin', $printed);
    }

    public function testCheckClickTokenSecretWarnsWhenUnset(): void
    {
        // Arrange
        putenv('SEARCH_SIGNALS_CLICK_TOKEN_SECRET');
        $console = new TestableSearchSignalsCheckInstallationConsole();
        $output = new BufferedOutput();

        // Act
        $console->publicCheckClickTokenSecret($output);

        // Assert
        $this->assertSame([], $console->getFailures());
        $this->assertCount(1, $console->getWarnings());
        $this->assertStringContainsString('SEARCH_SIGNALS_CLICK_TOKEN_SECRET is not set', $console->getWarnings()[0]);
    }

    public function testCheckClickTokenSecretWarnsWhenSetToAnEmptyString(): void
    {
        // Arrange
        putenv('SEARCH_SIGNALS_CLICK_TOKEN_SECRET=');
        $console = new TestableSearchSignalsCheckInstallationConsole();
        $output = new BufferedOutput();

        // Act
        $console->publicCheckClickTokenSecret($output);

        // Assert
        $this->assertCount(1, $console->getWarnings());
    }

    public function testCheckClickTokenSecretPassesWhenARealValueIsSet(): void
    {
        // Arrange
        putenv('SEARCH_SIGNALS_CLICK_TOKEN_SECRET=a-real-64-char-secret');
        $console = new TestableSearchSignalsCheckInstallationConsole();
        $output = new BufferedOutput();

        // Act
        $console->publicCheckClickTokenSecret($output);

        // Assert
        $this->assertSame([], $console->getWarnings());
        $this->assertStringContainsString('SEARCH_SIGNALS_CLICK_TOKEN_SECRET is set', $output->fetch());
    }
}
