<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Business\Drain;

use DateTime;
use JsonException;
use SprykerCommunity\Shared\SearchSignals\SearchSignalsConfig;
use SprykerCommunity\Zed\SearchSignals\Business\Capture\ClickEventWriterInterface;
use SprykerCommunity\Zed\SearchSignals\Business\Capture\ImpressionEventWriterInterface;
use SprykerCommunity\Zed\SearchSignals\Dependency\Client\SearchSignalsToStorageClientInterface;
use SprykerCommunity\Zed\SearchSignals\Persistence\SearchSignalsEntityManagerInterface;
use Throwable;

class QueueDrainer implements QueueDrainerInterface
{
    /**
     * @var int
     */
    protected const SCAN_BATCH_SIZE = 100;

    /**
     * @param \SprykerCommunity\Zed\SearchSignals\Dependency\Client\SearchSignalsToStorageClientInterface $storageClient
     * @param \SprykerCommunity\Zed\SearchSignals\Business\Capture\ImpressionEventWriterInterface $impressionEventWriter
     * @param \SprykerCommunity\Zed\SearchSignals\Business\Capture\ClickEventWriterInterface $clickEventWriter
     * @param \SprykerCommunity\Zed\SearchSignals\Persistence\SearchSignalsEntityManagerInterface $entityManager
     */
    public function __construct(
        protected SearchSignalsToStorageClientInterface $storageClient,
        protected ImpressionEventWriterInterface $impressionEventWriter,
        protected ClickEventWriterInterface $clickEventWriter,
        protected SearchSignalsEntityManagerInterface $entityManager,
    ) {
    }

    public function drain(): array
    {
        $impressionsDrained = $this->drainByPrefix(
            SearchSignalsConfig::STORAGE_KEY_PREFIX_IMPRESSION_EVENT,
            function (array $payload): void {
                $this->impressionEventWriter->write(
                    (string)$payload['query'],
                    (string)$payload['abstractSku'],
                    (int)$payload['rank'],
                    (string)$payload['storeName'],
                    (string)$payload['localeName'],
                );
            },
        );

        $clicksDrained = $this->drainByPrefix(
            SearchSignalsConfig::STORAGE_KEY_PREFIX_CLICK_EVENT,
            function (array $payload): void {
                $this->clickEventWriter->write(
                    (string)$payload['query'],
                    (string)$payload['abstractSku'],
                    (int)$payload['rank'],
                    (string)$payload['storeName'],
                    (string)$payload['localeName'],
                );
            },
        );

        // Channel 3b's "small version": deliberately unattributed, so there is no raw per-event row --
        // increments the daily bucket directly, dated by drain time rather than a captured_at that was
        // never recorded (acceptable at daily granularity for a cron-driven drain).
        $cartAddsDrained = $this->drainByPrefix(
            SearchSignalsConfig::STORAGE_KEY_PREFIX_CART_ADD_EVENT,
            function (array $payload): void {
                $this->entityManager->incrementProductCartAddCount(
                    (string)$payload['abstractSku'],
                    (string)$payload['storeName'],
                    (string)$payload['localeName'],
                    new DateTime(),
                    1,
                );
            },
        );

        return [
            'impressionsDrained' => $impressionsDrained,
            'clicksDrained' => $clicksDrained,
            'cartAddsDrained' => $cartAddsDrained,
        ];
    }

    /**
     * @param string $prefix
     * @param callable $writeCallback
     */
    protected function drainByPrefix(string $prefix, callable $writeCallback): int
    {
        $drainedCount = 0;
        $cursor = 0;

        do {
            $scanResult = $this->storageClient->scanKeys($prefix . '*', static::SCAN_BATCH_SIZE, $cursor);
            $keys = $scanResult->getKeys();

            if ($keys !== []) {
                // scanKeys() returns keys already carrying the Storage plugin's own internal prefix (e.g.
                // Redis' "kv:"), but getMulti()/deleteMulti() each re-apply that same prefix themselves --
                // passing scanKeys()'s own output straight through double-prefixes and silently misses
                // every key. Strip back to the bare key (starting at this package's own known prefix)
                // before calling either.
                $bareKeys = array_map(
                    static fn (string $key): string => substr($key, (int)strpos($key, $prefix)),
                    $keys,
                );

                $valuesByKey = $this->storageClient->getMulti($bareKeys);
                $keysToDelete = [];

                foreach ($valuesByKey as $prefixedKey => $value) {
                    $bareKey = substr((string)$prefixedKey, (int)strpos((string)$prefixedKey, $prefix));

                    if ($value === null || $value === false) {
                        continue;
                    }

                    try {
                        /** @var array<string, mixed> $payload */
                        $payload = json_decode((string)$value, true, flags: JSON_THROW_ON_ERROR);
                    } catch (JsonException) {
                        // Permanently malformed -- retrying forever would never succeed, so still delete it.
                        $keysToDelete[] = $bareKey;

                        continue;
                    }

                    try {
                        $writeCallback($payload);
                        $drainedCount++;
                        $keysToDelete[] = $bareKey;
                    } catch (Throwable) {
                        // A real write failure (e.g. a transient DB error) -- leave the key in place so the
                        // next drain run retries it, rather than silently losing the event.
                        continue;
                    }
                }

                if ($keysToDelete !== []) {
                    $this->storageClient->deleteMulti($keysToDelete);
                }
            }

            $cursor = $scanResult->getCursor();
        } while ($cursor !== 0);

        return $drainedCount;
    }
}
