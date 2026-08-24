<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchSignalsGuiPresentation\Presentation;

use SprykerCommunityTest\Zed\SearchSignalsGuiPresentation\SearchSignalsGuiPresentationTester;

/**
 * The Overview page loads without error and shows this package's own navigation entry — covers wiring,
 * not the underlying coverage numbers (those are covered by MetricCoverageReaderTest at the unit level).
 *
 * Run from the demoshop root, not standalone (this suite needs `\PyzTest\Shared\Testify\Helper\Environment`,
 * a project-level test helper that only exists in the demoshop's own vendor):
 * `vendor/bin/codecept run -c packages/spryker-community/search-signals/tests/SprykerCommunityTest/Zed/SearchSignalsGuiPresentation/codeception.yml`
 * via `docker/sdk testing` (not `docker/sdk cli` -- see webdriver-presentation-suite-infra-gotchas).
 * Live-verified 2026-08-24: 2/2 green.
 *
 * Auto-generated group annotations
 *
 * @group SprykerCommunityTest
 * @group Zed
 * @group SearchSignalsGuiPresentation
 * @group Presentation
 * @group OverviewCest
 * Add your own group annotations below this line
 */
class OverviewCest
{
    /**
     * @param \SprykerCommunityTest\Zed\SearchSignalsGuiPresentation\SearchSignalsGuiPresentationTester $i
     */
    public function _before(SearchSignalsGuiPresentationTester $i): void
    {
        $i->amZed();
        $i->amLoggedInUser();
    }

    /**
     * @param \SprykerCommunityTest\Zed\SearchSignalsGuiPresentation\SearchSignalsGuiPresentationTester $i
     */
    public function overviewPageLoadsAndShowsTheCoverageTableHeader(SearchSignalsGuiPresentationTester $i): void
    {
        $i->amOnPage('/search-signals/index');

        $i->see('Search Signals');
        $i->see('Metric coverage');
        $i->see('Impressions');
        $i->see('Clicks');
        $i->see('CTR');
        $i->see('Cart adds');
        $i->see('Orders');
    }

    /**
     * @param \SprykerCommunityTest\Zed\SearchSignalsGuiPresentation\SearchSignalsGuiPresentationTester $i
     */
    public function sidebarListsTheSearchSignalsEntryUnderSearchToolbox(SearchSignalsGuiPresentationTester $i): void
    {
        $i->amOnPage('/search-signals/index');

        $i->see('Search Toolbox');
        $i->see('Search Signals');
    }
}
