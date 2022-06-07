<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

namespace PrestaShop\Module\Mbo\Tests\Module\Workflow;

use PHPUnit\Framework\TestCase;
use PrestaShop\Module\Mbo\Module\Module;
use PrestaShop\Module\Mbo\Module\Workflow\ModuleStateMachine;
use Symfony\Component\Workflow\Transition;

class ModuleStateMachineTest extends TestCase
{
    private const TRANSITION_INSTALLED__ENABLED_MOBILE_DISABLED = [
        'name' => ModuleStateMachine::TRANSITION_INSTALLED__ENABLED_MOBILE_DISABLED,
        'froms' => [ModuleStateMachine::STATUS_INSTALLED],
        'tos' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_DISABLED],
    ];
    private const TRANSITION_INSTALLED__DISABLED_MOBILE_ENABLED = [
        'name' => ModuleStateMachine::TRANSITION_INSTALLED__DISABLED_MOBILE_ENABLED,
        'froms' => [ModuleStateMachine::STATUS_INSTALLED],
        'tos' => [ModuleStateMachine::STATUS_DISABLED__MOBILE_ENABLED],
    ];
    private const TRANSITION_INSTALLED__RESET = [
        'name' => ModuleStateMachine::TRANSITION_INSTALLED__RESET,
        'froms' => [ModuleStateMachine::STATUS_INSTALLED],
        'tos' => [ModuleStateMachine::STATUS_RESET],
    ];
    private const TRANSITION_INSTALLED__CONFIGURED = [
        'name' => ModuleStateMachine::TRANSITION_INSTALLED__CONFIGURED,
        'froms' => [ModuleStateMachine::STATUS_INSTALLED],
        'tos' => [ModuleStateMachine::STATUS_CONFIGURED],
    ];
    private const TRANSITION_INSTALLED__UPGRADED = [
        'name' => ModuleStateMachine::TRANSITION_INSTALLED__UPGRADED,
        'froms' => [ModuleStateMachine::STATUS_INSTALLED],
        'tos' => [ModuleStateMachine::STATUS_UPGRADED],
    ];
    private const TRANSITION_INSTALLED__UNINSTALLED = [
        'name' => ModuleStateMachine::TRANSITION_INSTALLED__UNINSTALLED,
        'froms' => [ModuleStateMachine::STATUS_INSTALLED],
        'tos' => [ModuleStateMachine::STATUS_UNINSTALLED],
    ];

    private const TRANSITION_ENABLED_MOBILE_ENABLED__ENABLED_MOBILE_DISABLED = [
        'name' => ModuleStateMachine::TRANSITION_ENABLED_MOBILE_ENABLED__ENABLED_MOBILE_DISABLED,
        'froms' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED],
        'tos' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_DISABLED],
    ];
    private const TRANSITION_ENABLED_MOBILE_ENABLED__DISABLED_MOBILE_ENABLED = [
        'name' => ModuleStateMachine::TRANSITION_ENABLED_MOBILE_ENABLED__DISABLED_MOBILE_ENABLED,
        'froms' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED],
        'tos' => [ModuleStateMachine::STATUS_DISABLED__MOBILE_ENABLED],
    ];
    private const TRANSITION_ENABLED_MOBILE_ENABLED__RESET = [
        'name' => ModuleStateMachine::TRANSITION_ENABLED_MOBILE_ENABLED__RESET,
        'froms' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED],
        'tos' => [ModuleStateMachine::STATUS_RESET],
    ];
    private const TRANSITION_ENABLED_MOBILE_ENABLED__UPGRADED = [
        'name' => ModuleStateMachine::TRANSITION_ENABLED_MOBILE_ENABLED__UPGRADED,
        'froms' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED],
        'tos' => [ModuleStateMachine::STATUS_UPGRADED],
    ];
    private const TRANSITION_ENABLED_MOBILE_ENABLED__CONFIGURED = [
        'name' => ModuleStateMachine::TRANSITION_ENABLED_MOBILE_ENABLED__CONFIGURED,
        'froms' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED],
        'tos' => [ModuleStateMachine::STATUS_CONFIGURED],
    ];
    private const TRANSITION_ENABLED_MOBILE_ENABLED__UNINSTALLED = [
        'name' => ModuleStateMachine::TRANSITION_ENABLED_MOBILE_ENABLED__UNINSTALLED,
        'froms' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED],
        'tos' => [ModuleStateMachine::STATUS_UNINSTALLED],
    ];

    private const TRANSITION_ENABLED_MOBILE_DISABLED__ENABLED_MOBILE_ENABLED = [
        'name' => ModuleStateMachine::TRANSITION_ENABLED_MOBILE_DISABLED__ENABLED_MOBILE_ENABLED,
        'froms' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_DISABLED],
        'tos' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED],
    ];
    private const TRANSITION_ENABLED_MOBILE_DISABLED__INSTALLED = [
        'name' => ModuleStateMachine::TRANSITION_ENABLED_MOBILE_DISABLED__INSTALLED,
        'froms' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_DISABLED],
        'tos' => [ModuleStateMachine::STATUS_INSTALLED],
    ];
    private const TRANSITION_ENABLED_MOBILE_DISABLED__RESET = [
        'name' => ModuleStateMachine::TRANSITION_ENABLED_MOBILE_DISABLED__RESET,
        'froms' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_DISABLED],
        'tos' => [ModuleStateMachine::STATUS_RESET],
    ];
    private const TRANSITION_ENABLED_MOBILE_DISABLED__UPGRADED = [
        'name' => ModuleStateMachine::TRANSITION_ENABLED_MOBILE_DISABLED__UPGRADED,
        'froms' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_DISABLED],
        'tos' => [ModuleStateMachine::STATUS_UPGRADED],
    ];
    private const TRANSITION_ENABLED_MOBILE_DISABLED__CONFIGURED = [
        'name' => ModuleStateMachine::TRANSITION_ENABLED_MOBILE_DISABLED__CONFIGURED,
        'froms' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_DISABLED],
        'tos' => [ModuleStateMachine::STATUS_CONFIGURED],
    ];
    private const TRANSITION_ENABLED_MOBILE_DISABLED__UNINSTALLED = [
        'name' => ModuleStateMachine::TRANSITION_ENABLED_MOBILE_DISABLED__UNINSTALLED,
        'froms' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_DISABLED],
        'tos' => [ModuleStateMachine::STATUS_UNINSTALLED],
    ];

    private const TRANSITION_DISABLED_MOBILE_ENABLED__INSTALLED = [
        'name' => ModuleStateMachine::TRANSITION_DISABLED_MOBILE_ENABLED__INSTALLED,
        'froms' => [ModuleStateMachine::STATUS_DISABLED__MOBILE_ENABLED],
        'tos' => [ModuleStateMachine::STATUS_INSTALLED],
    ];
    private const TRANSITION_DISABLED_MOBILE_ENABLED__ENABLED_MOBILE_ENABLED = [
        'name' => ModuleStateMachine::TRANSITION_DISABLED_MOBILE_ENABLED__ENABLED_MOBILE_ENABLED,
        'froms' => [ModuleStateMachine::STATUS_DISABLED__MOBILE_ENABLED],
        'tos' => [ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED],
    ];
    private const TRANSITION_DISABLED_MOBILE_ENABLED__RESET = [
        'name' => ModuleStateMachine::TRANSITION_DISABLED_MOBILE_ENABLED__RESET,
        'froms' => [ModuleStateMachine::STATUS_DISABLED__MOBILE_ENABLED],
        'tos' => [ModuleStateMachine::STATUS_RESET],
    ];
    private const TRANSITION_DISABLED_MOBILE_ENABLED__UPGRADED = [
        'name' => ModuleStateMachine::TRANSITION_DISABLED_MOBILE_ENABLED__UPGRADED,
        'froms' => [ModuleStateMachine::STATUS_DISABLED__MOBILE_ENABLED],
        'tos' => [ModuleStateMachine::STATUS_UPGRADED],
    ];
    private const TRANSITION_DISABLED_MOBILE_ENABLED__CONFIGURED = [
        'name' => ModuleStateMachine::TRANSITION_DISABLED_MOBILE_ENABLED__CONFIGURED,
        'froms' => [ModuleStateMachine::STATUS_DISABLED__MOBILE_ENABLED],
        'tos' => [ModuleStateMachine::STATUS_CONFIGURED],
    ];
    private const TRANSITION_DISABLED_MOBILE_ENABLED__UNINSTALLED = [
        'name' => ModuleStateMachine::TRANSITION_DISABLED_MOBILE_ENABLED__UNINSTALLED,
        'froms' => [ModuleStateMachine::STATUS_DISABLED__MOBILE_ENABLED],
        'tos' => [ModuleStateMachine::STATUS_UNINSTALLED],
    ];

    private const TRANSITION_UNINSTALLED__INSTALLED = [
        'name' => ModuleStateMachine::TRANSITION_UNINSTALLED__INSTALLED,
        'froms' => [ModuleStateMachine::STATUS_UNINSTALLED],
        'tos' => [ModuleStateMachine::STATUS_INSTALLED],
    ];

    /**
     * @var ModuleStateMachine
     */
    protected $moduleStateMachine;

    protected function setUp(): void
    {
        $this->moduleStateMachine = new ModuleStateMachine();
    }

    public function xtestModuleUninstalledPossibleTransitions()
    {
        $module = $this->getModule();

        $possibleTransitions = $this->moduleStateMachine->getEnabledTransitions($module);

        $this->assertSame(ModuleStateMachine::STATUS_UNINSTALLED, $module->getStatus());
        $this->assertSame([
            self::TRANSITION_UNINSTALLED__INSTALLED,
        ], $this->transitionsToArray($possibleTransitions));
    }

    public function xtestModuleInstalledPossibleTransitions()
    {
        $module = $this->getModule(['installed' => 1]);

        $possibleTransitions = $this->moduleStateMachine->getEnabledTransitions($module);

        $this->assertSame(ModuleStateMachine::STATUS_INSTALLED, $module->getStatus());
        $this->assertSame([
            self::TRANSITION_INSTALLED__ENABLED_MOBILE_DISABLED,
            self::TRANSITION_INSTALLED__DISABLED_MOBILE_ENABLED,
            self::TRANSITION_INSTALLED__CONFIGURED,
            self::TRANSITION_INSTALLED__RESET,
            self::TRANSITION_INSTALLED__UPGRADED,
            self::TRANSITION_INSTALLED__UNINSTALLED,
        ], $this->transitionsToArray($possibleTransitions));
    }

    public function xtestModuleEnabledMobileDisabledPossibleTransitions()
    {
        $module = $this->getModule(['installed' => 1, 'active' => 1, 'active_on_mobile' => 0]);

        $possibleTransitions = $this->moduleStateMachine->getEnabledTransitions($module);

        $this->assertSame(ModuleStateMachine::STATUS_ENABLED__MOBILE_DISABLED, $module->getStatus());
        $this->assertSame([
            self::TRANSITION_ENABLED_MOBILE_DISABLED__INSTALLED,
            self::TRANSITION_ENABLED_MOBILE_DISABLED__ENABLED_MOBILE_ENABLED,
            self::TRANSITION_ENABLED_MOBILE_DISABLED__CONFIGURED,
            self::TRANSITION_ENABLED_MOBILE_DISABLED__RESET,
            self::TRANSITION_ENABLED_MOBILE_DISABLED__UPGRADED,
            self::TRANSITION_ENABLED_MOBILE_DISABLED__UNINSTALLED,
        ], $this->transitionsToArray($possibleTransitions));
    }

    public function xtestModuleEnabledMobileEnabledPossibleTransitions()
    {
        $module = $this->getModule(['installed' => 1, 'active' => 1, 'active_on_mobile' => 1]);

        $possibleTransitions = $this->moduleStateMachine->getEnabledTransitions($module);

        $this->assertSame(ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED, $module->getStatus());
        $this->assertSame([
            self::TRANSITION_ENABLED_MOBILE_ENABLED__ENABLED_MOBILE_DISABLED,
            self::TRANSITION_ENABLED_MOBILE_ENABLED__DISABLED_MOBILE_ENABLED,
            self::TRANSITION_ENABLED_MOBILE_ENABLED__CONFIGURED,
            self::TRANSITION_ENABLED_MOBILE_ENABLED__RESET,
            self::TRANSITION_ENABLED_MOBILE_ENABLED__UPGRADED,
            self::TRANSITION_ENABLED_MOBILE_ENABLED__UNINSTALLED,
        ], $this->transitionsToArray($possibleTransitions));
    }

    public function xtestModuleDisabledMobileEnabledPossibleTransitions()
    {
        $module = $this->getModule(['installed' => 1, 'active' => 0, 'active_on_mobile' => 1]);

        $possibleTransitions = $this->moduleStateMachine->getEnabledTransitions($module);

        $this->assertSame(ModuleStateMachine::STATUS_DISABLED__MOBILE_ENABLED, $module->getStatus());
        $this->assertSame([
            self::TRANSITION_DISABLED_MOBILE_ENABLED__INSTALLED,
            self::TRANSITION_DISABLED_MOBILE_ENABLED__ENABLED_MOBILE_ENABLED,
            self::TRANSITION_DISABLED_MOBILE_ENABLED__CONFIGURED,
            self::TRANSITION_DISABLED_MOBILE_ENABLED__RESET,
            self::TRANSITION_DISABLED_MOBILE_ENABLED__UPGRADED,
            self::TRANSITION_DISABLED_MOBILE_ENABLED__UNINSTALLED,
        ], $this->transitionsToArray($possibleTransitions));
    }

    private function getModule(?array $dbAttributes = null): Module
    {
        return new Module(null, null, $dbAttributes);
    }

    private function transitionsToArray(array $transitions): array
    {
        $convertedTransitions = [];

        /**
         * @var Transition $transition
         */
        foreach ($transitions as $transition) {
            $convertedTransitions[] = [
                'name' => $transition->getName(),
                'froms' => $transition->getFroms(),
                'tos' => $transition->getTos(),
            ];
        }

        return $convertedTransitions;
    }
}
