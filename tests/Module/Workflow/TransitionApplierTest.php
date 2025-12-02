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

use PrestaShop\Module\Mbo\Module\Exception\TransitionFailedException;
use PrestaShop\Module\Mbo\Module\ValueObject\ModuleTransitionCommand;
use PrestaShop\Module\Mbo\Module\Workflow\TransitionApplier;
use PrestaShop\Module\Mbo\Module\Workflow\TransitionInterface;
use PrestaShop\Module\Mbo\Module\Workflow\TransitionsManager;
use Symfony\Component\String\UnicodeString;

class TransitionApplierTest extends AbstractTransitionTest
{
    protected function setUp(): void
    {
        // No setup required for this test class
    }

    /**
     * @dataProvider getModuleAttributesAndAppliedTransitions
     */
    public function testApplyTransitions(
        array $moduleAttributes,
        string $transitionCommand,
        string $transitionName,
        string $targetStatus
    ) {
        $module = $this->getTransitionModule(
            $moduleAttributes['name'],
            $moduleAttributes['version'],
            $moduleAttributes['installed'],
            $moduleAttributes['active_on_mobile'],
            $moduleAttributes['active']
        );

        $transitionsManager = $this->createMock(TransitionsManager::class);
        $methodName = (new UnicodeString($transitionName))->camel()->toString();

        $transitionsManager
            ->expects($this->once())
            ->method($methodName)
            ->with($module, [])
        ;

        $transitionApplier = new TransitionApplier($transitionsManager);

        // Application will throw an exception because transitionManager object is a mock
        $this->expectException(TransitionFailedException::class);
        $transitionApplier->apply($module, $transitionName);
    }

    public function getModuleAttributesAndAppliedTransitions()
    {
        yield [
            [
                'name' => 'x_module',
                'version' => '1.0.0',
                'installed' => true,
                'active' => true,
                'active_on_mobile' => true,
            ], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_MOBILE_DISABLE, //transition command
            self::TRANSITION_NAME_ENABLED_MOBILE_ENABLED__ENABLED_MOBILE_DISABLED, //transitionName
            TransitionInterface::STATUS_ENABLED__MOBILE_DISABLED, //target status
        ];

        yield [
            [
                'name' => 'x_module',
                'version' => '1.0.0',
                'installed' => true,
                'active' => true,
                'active_on_mobile' => true,
            ], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_DISABLE, //transition command
            self::TRANSITION_NAME_ENABLED_MOBILE_ENABLED__DISABLED_MOBILE_ENABLED, //transitionName
            TransitionInterface::STATUS_DISABLED__MOBILE_ENABLED, //target status
        ];

        yield [
            [
                'name' => 'x_module',
                'version' => '1.0.0',
                'installed' => true,
                'active' => true,
                'active_on_mobile' => true,
            ], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_RESET, //transition command
            self::TRANSITION_NAME_ENABLED_MOBILE_ENABLED__RESET, //transitionName
            TransitionInterface::STATUS_RESET, //target status
        ];

        yield [
            [
                'name' => 'x_module',
                'version' => '1.0.0',
                'installed' => true,
                'active' => true,
                'active_on_mobile' => true,
            ], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_CONFIGURE, //transition command
            self::TRANSITION_NAME_ENABLED_MOBILE_ENABLED__CONFIGURED, //transitionName
            TransitionInterface::STATUS_CONFIGURED, //target status
        ];

        yield [
            [
                'name' => 'x_module',
                'version' => '1.0.0',
                'installed' => true,
                'active' => true,
                'active_on_mobile' => true,
            ], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_UPGRADE, //transition command
            self::TRANSITION_NAME_ENABLED_MOBILE_ENABLED__UPGRADED, //transitionName
            TransitionInterface::STATUS_UPGRADED, //target status
        ];

        yield [
            [
                'name' => 'x_module',
                'version' => '1.0.0',
                'installed' => true,
                'active' => true,
                'active_on_mobile' => true,
            ], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_UNINSTALL, //transition command
            self::TRANSITION_NAME_ENABLED_MOBILE_ENABLED__UNINSTALLED, //transitionName
            TransitionInterface::STATUS_UNINSTALLED, //target status
        ];

        yield [
            [
                'name' => 'x_module',
                'version' => '1.0.0',
                'installed' => true,
                'active' => true,
                'active_on_mobile' => true,
            ], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_MOBILE_DISABLE, //transition command
            self::TRANSITION_NAME_ENABLED_MOBILE_ENABLED__ENABLED_MOBILE_DISABLED, //transitionName
            TransitionInterface::STATUS_ENABLED__MOBILE_DISABLED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => true, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_DISABLE, //transition command
            self::TRANSITION_NAME_ENABLED_MOBILE_ENABLED__DISABLED_MOBILE_ENABLED, //transitionName
            TransitionInterface::STATUS_DISABLED__MOBILE_ENABLED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => true, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_RESET, //transition command
            self::TRANSITION_NAME_ENABLED_MOBILE_ENABLED__RESET, //transitionName
            TransitionInterface::STATUS_RESET, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => true, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_UPGRADE, //transition command
            self::TRANSITION_NAME_ENABLED_MOBILE_ENABLED__UPGRADED, //transitionName
            TransitionInterface::STATUS_UPGRADED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => true, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_CONFIGURE, //transition command
            self::TRANSITION_NAME_ENABLED_MOBILE_ENABLED__CONFIGURED, //transitionName
            TransitionInterface::STATUS_CONFIGURED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => true, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_UNINSTALL, //transition command
            self::TRANSITION_NAME_ENABLED_MOBILE_ENABLED__UNINSTALLED, //transitionName
            TransitionInterface::STATUS_UNINSTALLED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => true, 'active_on_mobile' => false], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_MOBILE_ENABLE, //transition command
            self::TRANSITION_NAME_ENABLED_MOBILE_DISABLED__ENABLED_MOBILE_ENABLED, //transitionName
            TransitionInterface::STATUS_ENABLED__MOBILE_ENABLED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => true, 'active_on_mobile' => false], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_RESET, //transition command
            self::TRANSITION_NAME_ENABLED_MOBILE_DISABLED__RESET, //transitionName
            TransitionInterface::STATUS_RESET, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => true, 'active_on_mobile' => false], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_UPGRADE, //transition command
            self::TRANSITION_NAME_ENABLED_MOBILE_DISABLED__UPGRADED, //transitionName
            TransitionInterface::STATUS_UPGRADED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => true, 'active_on_mobile' => false], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_CONFIGURE, //transition command
            self::TRANSITION_NAME_ENABLED_MOBILE_DISABLED__CONFIGURED, //transitionName
            TransitionInterface::STATUS_CONFIGURED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => true, 'active_on_mobile' => false], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_UNINSTALL, //transition command
            self::TRANSITION_NAME_ENABLED_MOBILE_DISABLED__UNINSTALLED, //transitionName
            TransitionInterface::STATUS_UNINSTALLED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => false, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_ENABLE, //transition command
            self::TRANSITION_NAME_DISABLED_MOBILE_ENABLED__ENABLED_MOBILE_ENABLED, //transitionName
            TransitionInterface::STATUS_ENABLED__MOBILE_ENABLED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => false, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_ENABLE, //transition command
            self::TRANSITION_NAME_DISABLED_MOBILE_ENABLED__ENABLED_MOBILE_ENABLED, //transitionName
            TransitionInterface::STATUS_ENABLED__MOBILE_ENABLED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => false, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_RESET, //transition command
            self::TRANSITION_NAME_DISABLED_MOBILE_ENABLED__RESET, //transitionName
            TransitionInterface::STATUS_RESET, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => false, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_UPGRADE, //transition command
            self::TRANSITION_NAME_DISABLED_MOBILE_ENABLED__UPGRADED, //transitionName
            TransitionInterface::STATUS_UPGRADED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => false, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_CONFIGURE, //transition command
            self::TRANSITION_NAME_DISABLED_MOBILE_ENABLED__CONFIGURED, //transitionName
            TransitionInterface::STATUS_CONFIGURED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => true, 'active' => false, 'active_on_mobile' => true], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_UNINSTALL, //transition command
            self::TRANSITION_NAME_DISABLED_MOBILE_ENABLED__UNINSTALLED, //transitionName
            TransitionInterface::STATUS_UNINSTALLED, //target status
        ];

        yield [
            ['name' => 'x_module', 'version' => '1.0.0', 'installed' => false, 'active' => false, 'active_on_mobile' => false], //module attributes
            ModuleTransitionCommand::MODULE_COMMAND_INSTALL, //transition command
            self::TRANSITION_NAME_UNINSTALLED__ENABLED_MOBILE_ENABLED, //transitionName
            TransitionInterface::STATUS_ENABLED__MOBILE_ENABLED, //target status
        ];
    }
}
