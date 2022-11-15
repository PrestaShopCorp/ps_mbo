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

namespace PrestaShop\Module\Mbo\Tests\Module;

use PHPUnit\Framework\TestCase;
use PrestaShop\Module\Mbo\Module\TransitionModule;
use PrestaShop\Module\Mbo\Module\Workflow\ModuleStateMachine;

class TransitionModuleTest extends TestCase
{
    /**
     * @dataProvider getModuleAttributesAndStatus
     */
    public function testGetStatus(array $dbAttributes, $expectedStatus)
    {
        $module = $this->getModule($dbAttributes);

        $this->assertSame($expectedStatus, $module->getStatus());
    }

    public function getModuleAttributesAndStatus()
    {
        yield [
            ['name' => 'my_module', 'version' => '1.0.0', 'installed' => false, 'active_on_mobile' => false, 'active' => false],
            ModuleStateMachine::STATUS_UNINSTALLED,
        ];

        yield [
            ['name' => 'my_module', 'version' => '1.0.0', 'installed' => false, 'active_on_mobile' => true, 'active' => true],
            ModuleStateMachine::STATUS_UNINSTALLED,
        ];

        yield [
            ['name' => 'my_module', 'version' => '1.0.0', 'installed' => true, 'active_on_mobile' => true, 'active' => true],
            ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED,
        ];

        yield [
            ['name' => 'my_module', 'version' => '1.0.0', 'installed' => true, 'active_on_mobile' => false, 'active' => true],
            ModuleStateMachine::STATUS_ENABLED__MOBILE_DISABLED,
        ];

        yield [
            ['name' => 'my_module', 'version' => '1.0.0', 'installed' => true, 'active_on_mobile' => true, 'active' => false],
            ModuleStateMachine::STATUS_DISABLED__MOBILE_ENABLED,
        ];

        yield [
            ['name' => 'my_module', 'version' => '1.0.0', 'installed' => true, 'active_on_mobile' => false, 'active' => false],
            ModuleStateMachine::STATUS_DISABLED__MOBILE_DISABLED,
        ];
    }

    private function getModule(array $attributes): TransitionModule
    {
        return new TransitionModule(
            $attributes['name'],
            $attributes['version'],
            $attributes['installed'],
            $attributes['active_on_mobile'],
            $attributes['active']
        );
    }
}
