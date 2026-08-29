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
 * @method \SprykerCommunity\Zed\SearchSignals\Business\SearchSignalsFacadeInterface getFacade()
 */
class SearchSignalsEmitConsole extends Console
{
    /**
     * @var string
     */
    public const COMMAND_NAME = 'search-signals:emit';

    /**
     * @var string
     */
    protected const ARGUMENT_STORE = 'store';

    /**
     * @var string
     */
    protected const ARGUMENT_LOCALE = 'locale';

    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME)
            ->setDescription('Shrinks each product\'s rollup CTR and writes the search-ranking product-metric CSV contract.')
            ->addArgument(static::ARGUMENT_STORE, InputArgument::REQUIRED, 'Store name, e.g. DE.')
            ->addArgument(static::ARGUMENT_LOCALE, InputArgument::REQUIRED, 'Locale name, e.g. de_DE.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storeName = (string)$input->getArgument(static::ARGUMENT_STORE);
        $localeName = (string)$input->getArgument(static::ARGUMENT_LOCALE);

        $rowCount = $this->getFacade()->emitProductMetricCsv($storeName, $localeName);

        $output->writeln(sprintf('Wrote %d row(s) for %s/%s.', $rowCount, $storeName, $localeName));

        return static::CODE_SUCCESS;
    }
}
