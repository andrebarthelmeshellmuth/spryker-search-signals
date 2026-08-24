<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchSignals\Business\Shrinkage;

use Codeception\Test\Unit;
use InvalidArgumentException;
use SprykerCommunity\Zed\SearchSignals\Business\Shrinkage\BayesianShrinkageCalculator;

/**
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchSignals
 * @group Business
 * @group Shrinkage
 * @group BayesianShrinkageCalculatorTest
 * @group Portable
 */
class BayesianShrinkageCalculatorTest extends Unit
{
    public function testZeroImpressionsShrinksAllTheWayToThePrior(): void
    {
        $calculator = new BayesianShrinkageCalculator();

        $this->assertSame(0.02, $calculator->calculate(0, 0, 50.0, 0.02));
    }

    /**
     * The exact pathological case the package's own SearchSignalsConfig docblock describes: a
     * 1-impression/1-click product (raw CTR 1.0) must shrink to well below a genuinely well-supported
     * 50-click/1000-impression product's shrunk value.
     */
    public function testAPathologicalOneImpressionOneClickProductShrinksBelowAWellSupportedProduct(): void
    {
        // Arrange
        $calculator = new BayesianShrinkageCalculator();
        $alpha = 50.0;
        $prior = 0.02;

        // Act
        $pathological = $calculator->calculate(1, 1, $alpha, $prior);
        $wellSupported = $calculator->calculate(50, 1000, $alpha, $prior);

        // Assert
        $this->assertLessThan($wellSupported, $pathological);
    }

    public function testHighImpressionCountLetsTheRawCtrDominateOverThePrior(): void
    {
        // Arrange -- a huge, genuinely 50%-CTR product should shrink to something close to 0.5, not the prior
        $calculator = new BayesianShrinkageCalculator();

        // Act
        $shrunk = $calculator->calculate(500000, 1000000, 50.0, 0.02);

        // Assert
        $this->assertEqualsWithDelta(0.5, $shrunk, 0.001);
    }

    public function testNegativeClicksThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new BayesianShrinkageCalculator())->calculate(-1, 10, 50.0, 0.02);
    }

    public function testNegativeImpressionsThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new BayesianShrinkageCalculator())->calculate(1, -10, 50.0, 0.02);
    }

    public function testNegativeAlphaThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new BayesianShrinkageCalculator())->calculate(1, 10, -1.0, 0.02);
    }

    public function testZeroAlphaReturnsTheRawUnshrunkCtr(): void
    {
        $calculator = new BayesianShrinkageCalculator();

        $this->assertSame(0.25, $calculator->calculate(5, 20, 0.0, 0.02));
    }
}
