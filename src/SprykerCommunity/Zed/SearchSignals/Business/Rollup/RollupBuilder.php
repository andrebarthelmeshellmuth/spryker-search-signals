<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Business\Rollup;

use DateTime;
use DateTimeInterface;
use SprykerCommunity\Zed\SearchSignals\Persistence\SearchSignalsEntityManagerInterface;
use SprykerCommunity\Zed\SearchSignals\Persistence\SearchSignalsRepositoryInterface;
use SprykerCommunity\Zed\SearchSignals\SearchSignalsConfig;

class RollupBuilder implements RollupBuilderInterface
{
    /**
     * @param \SprykerCommunity\Zed\SearchSignals\Persistence\SearchSignalsRepositoryInterface $repository
     * @param \SprykerCommunity\Zed\SearchSignals\Persistence\SearchSignalsEntityManagerInterface $entityManager
     * @param \SprykerCommunity\Zed\SearchSignals\SearchSignalsConfig $config
     */
    public function __construct(
        protected SearchSignalsRepositoryInterface $repository,
        protected SearchSignalsEntityManagerInterface $entityManager,
        protected SearchSignalsConfig $config,
    ) {
    }

    public function rollUp(DateTimeInterface $since): array
    {
        $impressionEvents = $this->repository->getImpressionEventsCapturedSince($since);

        foreach ($impressionEvents as $event) {
            $this->entityManager->incrementProductCtrDailyBucket(
                $event['abstractSku'],
                $event['storeName'],
                $event['localeName'],
                $event['capturedAt'],
                1,
                0,
            );
            $this->entityManager->incrementQueryCtrWeeklyBucket(
                $event['query'],
                $event['abstractSku'],
                $event['storeName'],
                $event['localeName'],
                $this->mondayOfWeek($event['capturedAt']),
                1,
                0,
            );
        }

        $clickEvents = $this->repository->getClickEventsCapturedSince($since);

        foreach ($clickEvents as $event) {
            $this->entityManager->incrementProductCtrDailyBucket(
                $event['abstractSku'],
                $event['storeName'],
                $event['localeName'],
                $event['capturedAt'],
                0,
                1,
            );
            $this->entityManager->incrementQueryCtrWeeklyBucket(
                $event['query'],
                $event['abstractSku'],
                $event['storeName'],
                $event['localeName'],
                $this->mondayOfWeek($event['capturedAt']),
                0,
                1,
            );
        }

        $retentionCutoff = (new DateTime())->modify(sprintf('-%d days', $this->config->getRawEventRetentionDays()));
        $deletedRawEventCount = $this->entityManager->deleteRawEventsBefore($retentionCutoff);

        return [
            'impressionsRolledUp' => count($impressionEvents),
            'clicksRolledUp' => count($clickEvents),
            'rawEventsDeleted' => $deletedRawEventCount,
        ];
    }

    /**
     * @param \DateTimeInterface $dateTime
     */
    protected function mondayOfWeek(DateTimeInterface $dateTime): DateTimeInterface
    {
        return (new DateTime($dateTime->format('Y-m-d')))->modify('monday this week');
    }
}
