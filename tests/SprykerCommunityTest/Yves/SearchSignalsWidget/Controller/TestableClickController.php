<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Yves\SearchSignalsWidget\Controller;

use SprykerCommunity\Yves\SearchSignalsWidget\Controller\ClickController;
use SprykerCommunity\Yves\SearchSignalsWidget\SearchSignalsWidgetFactory;

/**
 * Exposes a way to inject a mocked Factory, bypassing the real FactoryResolver (which needs a full Yves
 * DI container this Portable suite doesn't have) -- purely for {@see ClickControllerTest}.
 */
class TestableClickController extends ClickController
{
    protected SearchSignalsWidgetFactory $testFactory;

    public function setTestFactory(SearchSignalsWidgetFactory $testFactory): void
    {
        $this->testFactory = $testFactory;
    }

    protected function getFactory(): SearchSignalsWidgetFactory
    {
        return $this->testFactory;
    }
}
