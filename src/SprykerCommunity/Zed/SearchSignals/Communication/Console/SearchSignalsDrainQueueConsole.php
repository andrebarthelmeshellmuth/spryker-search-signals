<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Communication\Console;

use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Drains impression/click events the Client layer wrote to the Storage KV store (see the
 * search-signals plan's channel 3a) into the raw event tables. Intended to run on a short cron cadence,
 * ahead of the (much less frequent) rollup command.
 *
 * @method \SprykerCommunity\Zed\SearchSignals\Business\SearchSignalsFacadeInterface getFacade()
 */
class SearchSignalsDrainQueueConsole extends Console
{
    /**
     * @var string
     */
    public const COMMAND_NAME = 'search-signals:drain-queue';

    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME)
            ->setDescription('Drains Storage-KV impression/click events written by the Client layer into the raw event tables.');

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
        $result = $this->getFacade()->drainQueue();

        $output->writeln(sprintf(
            'Drained %d impression(s), %d click(s), and %d cart-add(s).',
            $result['impressionsDrained'],
            $result['clicksDrained'],
            $result['cartAddsDrained'],
        ));

        return static::CODE_SUCCESS;
    }
}
