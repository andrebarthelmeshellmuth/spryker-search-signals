<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Communication\Console;

use DateTime;
use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \SprykerCommunity\Zed\SearchSignals\Business\SearchSignalsFacadeInterface getFacade()
 */
class SearchSignalsRollupConsole extends Console
{
    /**
     * @var string
     */
    public const COMMAND_NAME = 'search-signals:rollup';

    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME)
            ->setDescription('Folds raw impression/click events into the product-daily and query-weekly rollup buckets, then purges raw events past their retention window.');

        parent::configure();
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter $input is mandated by the Console base class.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Deliberately re-processes the last 2 days on every run (rollup increments are additive and safe
        // to re-apply, see RollupBuilder) rather than tracking a precise watermark -- covers a cron that
        // missed a run without any state to get wrong.
        $since = (new DateTime())->modify('-2 days');
        $result = $this->getFacade()->rollUp($since);

        $output->writeln(sprintf(
            'Rolled up %d impression(s) and %d click(s); deleted %d raw event(s) past retention.',
            $result['impressionsRolledUp'],
            $result['clicksRolledUp'],
            $result['rawEventsDeleted'],
        ));

        return static::CODE_SUCCESS;
    }
}
