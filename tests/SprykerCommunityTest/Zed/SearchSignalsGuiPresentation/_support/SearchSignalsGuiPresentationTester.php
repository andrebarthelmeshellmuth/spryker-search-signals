<?php

/**
 * This file is part of the spryker-community/search-signals package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerCommunityTest\Zed\SearchSignalsGuiPresentation;

use Codeception\Actor;
use Exception;

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(\SprykerCommunityTest\Zed\SearchSignalsGuiPresentation\PHPMD)
 */
class SearchSignalsGuiPresentationTester extends Actor
{
    use _generated\SearchSignalsGuiPresentationTesterActions;

    /**
     * @param string $selector
     */
    public function tryToSeeElement(string $selector): bool
    {
        try {
            $this->seeElement($selector);

            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Runs a real console command inside this same container -- used to actually drive fixture state
     * (drain-queue, rollup, emit) for a Presentation test rather than fabricating rows directly.
     *
     * @param string $command e.g. "search-signals:drain-queue"
     *
     * @return string Combined stdout+stderr, for assertions/debugging.
     */
    public function runConsoleCommand(string $command): string
    {
        $output = shell_exec(sprintf('cd /data && vendor/bin/console %s 2>&1', $command));

        return (string)$output;
    }
}
