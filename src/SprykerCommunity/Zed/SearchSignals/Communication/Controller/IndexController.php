<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Communication\Controller;

use Spryker\Zed\Kernel\Communication\Controller\AbstractController;

/**
 * Read-only overview: per store+locale, how much of each metric this package produces has actually been
 * captured (see {@see \SprykerCommunity\Zed\SearchSignals\Business\Coverage\MetricCoverageReaderInterface}).
 * Deliberately a plain server-rendered table, not a SprykerTable widget — the row count is bounded by
 * the number of store+locale combinations a shop has (typically single digits), not by data volume, so
 * the extra AJAX/paging machinery a SprykerTable brings would be overhead with nothing to page through.
 *
 * @method \SprykerCommunity\Zed\SearchSignals\Communication\SearchSignalsCommunicationFactory getFactory()
 * @method \SprykerCommunity\Zed\SearchSignals\Business\SearchSignalsFacadeInterface getFacade()
 */
class IndexController extends AbstractController
{
    /**
     * @return array<string, mixed>
     */
    public function indexAction(): array
    {
        return $this->viewResponse([
            'coverage' => $this->getFacade()->getMetricCoverageOverview(),
        ]);
    }
}
