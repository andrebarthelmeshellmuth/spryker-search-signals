<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchSignals\Communication\Console;

use SprykerCommunity\Zed\SearchSignals\Communication\Console\SearchSignalsCheckInstallationConsole;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Exposes the DI-free protected check methods and the two accumulator arrays publicly, purely for
 * {@see SearchSignalsCheckInstallationConsoleTest} -- production code is untouched.
 */
class TestableSearchSignalsCheckInstallationConsole extends SearchSignalsCheckInstallationConsole
{
    public function publicCheckCoreNamespace(OutputInterface $output): void
    {
        $this->checkCoreNamespace($output);
    }

    public function publicCheckPluginClasses(OutputInterface $output): void
    {
        $this->checkPluginClasses($output);
    }

    public function publicCheckClickTokenSecret(OutputInterface $output): void
    {
        $this->checkClickTokenSecret($output);
    }

    /**
     * @return array<string>
     */
    public function getFailures(): array
    {
        return $this->failures;
    }

    /**
     * @return array<string>
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }
}
