<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunity\Zed\SearchSignals\Business\Shrinkage;

use InvalidArgumentException;

/**
 * Bayesian shrinkage (`(clicks + alpha * prior) / (impressions + alpha)`) -- pulls a low-sample-size CTR
 * toward the catalogue-wide prior instead of trusting it at face value. Confirmed live via the P0 spike
 * (see the search-signals plan) that a 1-impression/1-click product otherwise gets the exact same
 * normalized ranking score as a product with thousands of genuinely well-supported impressions -- this
 * is the fix.
 *
 * `alpha` is the "how many prior impressions worth of skepticism" knob: a product's own raw CTR only
 * starts to dominate the shrunk value once its real impression count is large relative to alpha.
 */
class BayesianShrinkageCalculator implements BayesianShrinkageCalculatorInterface
{
    public function calculate(int $clicks, int $impressions, float $alpha, float $prior): float
    {
        if ($impressions < 0 || $clicks < 0) {
            throw new InvalidArgumentException('Clicks and impressions must be non-negative.');
        }

        if ($alpha < 0.0) {
            throw new InvalidArgumentException('Alpha must be non-negative.');
        }

        return ($clicks + $alpha * $prior) / ($impressions + $alpha);
    }
}
