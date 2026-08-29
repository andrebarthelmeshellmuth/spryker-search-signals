<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Business\Shrinkage;

interface BayesianShrinkageCalculatorInterface
{
    /**
     * @param int $clicks
     * @param int $impressions
     * @param float $alpha Prior weight -- see {@see SprykerCommunity\Zed\SearchSignals\Business\Shrinkage\BayesianShrinkageCalculator}.
     * @param float $prior Catalogue-wide mean CTR for this store/locale.
     *
     * @throws \InvalidArgumentException
     */
    public function calculate(int $clicks, int $impressions, float $alpha, float $prior): float;
}
