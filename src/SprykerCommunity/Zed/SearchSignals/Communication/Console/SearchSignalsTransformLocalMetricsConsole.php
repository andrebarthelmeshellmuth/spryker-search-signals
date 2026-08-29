<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Communication\Console;

use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Channel 2 (see the search-signals plan): same cron cadence as {@see SearchSignalsEmitConsole}, but
 * independent of it -- this runs whatever plugins a project registered in
 * {@see \SprykerCommunity\Zed\SearchSignals\SearchSignalsDependencyProvider::getMetricTransformerPlugins()}
 * and is a genuine no-op (0 rows, exit success) if none are.
 *
 * @method \SprykerCommunity\Zed\SearchSignals\Business\SearchSignalsFacadeInterface getFacade()
 */
class SearchSignalsTransformLocalMetricsConsole extends Console
{
    /**
     * @var string
     */
    public const COMMAND_NAME = 'search-signals:transform-local-metrics';

    /**
     * @var string
     */
    protected const ARGUMENT_STORE = 'store';

    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME)
            ->setDescription('Runs every registered local-data metric transformer plugin (Channel 2, e.g. stock level, delivery time) and writes that store\'s CSV.')
            ->addArgument(static::ARGUMENT_STORE, InputArgument::REQUIRED, 'Store name, e.g. DE.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storeName = (string)$input->getArgument(static::ARGUMENT_STORE);

        $rowCount = $this->getFacade()->transformLocalMetrics($storeName);

        $output->writeln(sprintf('Wrote %d row(s) for %s.', $rowCount, $storeName));

        return static::CODE_SUCCESS;
    }
}
