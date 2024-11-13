<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace PrestaShop\Module\Mbo\Tests\Module\Workflow;

use PrestaShop\Module\Mbo\Module\Workflow\TransitionBuilder;
use PrestaShop\Module\Mbo\Module\Workflow\TransitionInterface;

class TransitionBuilderTest extends AbstractTransitionTest
{
    /**
     * @var TransitionBuilder
     */
    protected $transitionBuilder;

    protected function setUp(): void
    {
        $this->transitionBuilder = new TransitionBuilder();
    }

    public function testModuleUninstalledPossibleTransitions()
    {
        $module = $this->getTransitionModule('x_module', '1.0.0', false, false, false);

        $possibleTransitions = $this->transitionBuilder->getModuleAllowedTransitions($module);

        $this->assertSame(TransitionInterface::STATUS_UNINSTALLED, $module->getStatus());
        $this->assertSame([
            self::TRANSITION_UNINSTALLED__ENABLED_MOBILE_ENABLED,
        ], $this->transitionsToArray($possibleTransitions));
    }

    public function testModuleDisabledMobileDisabledPossibleTransitions()
    {
        $module = $this->getTransitionModule('x_module', '1.0.0', true, false, false);

        $possibleTransitions = $this->transitionBuilder->getModuleAllowedTransitions($module);

        $this->assertSame(TransitionInterface::STATUS_DISABLED__MOBILE_DISABLED, $module->getStatus());
        $this->assertSame([
            self::TRANSITION_DISABLED_MOBILE_DISABLED__RESET,
            self::TRANSITION_DISABLED_MOBILE_DISABLED__UPGRADED,
            self::TRANSITION_DISABLED_MOBILE_DISABLED__CONFIGURED,
            self::TRANSITION_DISABLED_MOBILE_DISABLED__UNINSTALLED,
            self::TRANSITION_DISABLED_MOBILE_DISABLED__DISABLED_MOBILE_ENABLED,
            self::TRANSITION_DISABLED_MOBILE_DISABLED__ENABLED_MOBILE_DISABLED,
        ], $this->transitionsToArray($possibleTransitions));
    }

    public function testModuleEnabledMobileDisabledPossibleTransitions()
    {
        $module = $this->getTransitionModule('x_module', '1.0.0', true, false, true);

        $possibleTransitions = $this->transitionBuilder->getModuleAllowedTransitions($module);

        $this->assertSame(TransitionInterface::STATUS_ENABLED__MOBILE_DISABLED, $module->getStatus());
        $this->assertSame([
            self::TRANSITION_ENABLED_MOBILE_DISABLED__RESET,
            self::TRANSITION_ENABLED_MOBILE_DISABLED__UPGRADED,
            self::TRANSITION_ENABLED_MOBILE_DISABLED__CONFIGURED,
            self::TRANSITION_ENABLED_MOBILE_DISABLED__UNINSTALLED,
            self::TRANSITION_ENABLED_MOBILE_DISABLED__ENABLED_MOBILE_ENABLED,
            self::TRANSITION_ENABLED_MOBILE_DISABLED__DISABLED_MOBILE_DISABLED,
        ], $this->transitionsToArray($possibleTransitions));
    }

    public function testModuleEnabledMobileEnabledPossibleTransitions()
    {
        $module = $this->getTransitionModule('x_module', '1.0.0', true, true, true);

        $possibleTransitions = $this->transitionBuilder->getModuleAllowedTransitions($module);

        $this->assertSame(TransitionInterface::STATUS_ENABLED__MOBILE_ENABLED, $module->getStatus());
        $this->assertSame([
            self::TRANSITION_ENABLED_MOBILE_ENABLED__RESET,
            self::TRANSITION_ENABLED_MOBILE_ENABLED__UPGRADED,
            self::TRANSITION_ENABLED_MOBILE_ENABLED__CONFIGURED,
            self::TRANSITION_ENABLED_MOBILE_ENABLED__UNINSTALLED,
            self::TRANSITION_ENABLED_MOBILE_ENABLED__ENABLED_MOBILE_DISABLED,
            self::TRANSITION_ENABLED_MOBILE_ENABLED__DISABLED_MOBILE_ENABLED,
        ], $this->transitionsToArray($possibleTransitions));
    }

    public function testModuleDisabledMobileEnabledPossibleTransitions()
    {
        $module = $this->getTransitionModule('x_module', '1.0.0', true, true, false);

        $possibleTransitions = $this->transitionBuilder->getModuleAllowedTransitions($module);

        $this->assertSame(TransitionInterface::STATUS_DISABLED__MOBILE_ENABLED, $module->getStatus());
        $this->assertSame([
            self::TRANSITION_DISABLED_MOBILE_ENABLED__RESET,
            self::TRANSITION_DISABLED_MOBILE_ENABLED__UPGRADED,
            self::TRANSITION_DISABLED_MOBILE_ENABLED__CONFIGURED,
            self::TRANSITION_DISABLED_MOBILE_ENABLED__UNINSTALLED,
            self::TRANSITION_DISABLED_MOBILE_ENABLED__ENABLED_MOBILE_ENABLED,
            self::TRANSITION_DISABLED_MOBILE_ENABLED__DISABLED_MOBILE_DISABLED,
        ], $this->transitionsToArray($possibleTransitions));
    }

    /**
     * @dataProvider getModuleAttributesAndAppliedTransitions
     */
    public function testGetTransition(
        array $moduleAttributes,
        string $transitionCommand,
        string $transitionName,
        string $targetStatus,
    ) {
        $module = $this->getTransitionModule(
            $moduleAttributes['name'],
            $moduleAttributes['version'],
            $moduleAttributes['installed'],
            $moduleAttributes['active_on_mobile'],
            $moduleAttributes['active']
        );

        $this->assertSame($transitionName, $this->transitionBuilder->getTransition($module, $transitionCommand));
    }
}
