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

class ModuleTest extends TestCase
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
            [],
            ModuleStateMachine::STATUS_UNINSTALLED,
        ];

        yield [
            ['installed' => 0, 'active' => 0, 'active_on_mobile' => 0],
            ModuleStateMachine::STATUS_UNINSTALLED,
        ];

        yield [
            ['installed' => 1],
            ModuleStateMachine::STATUS_INSTALLED,
        ];

        yield [
            ['installed' => 1, 'active' => 0, 'active_on_mobile' => 0],
            ModuleStateMachine::STATUS_INSTALLED,
        ];

        yield [
            ['installed' => 1, 'active' => 0],
            ModuleStateMachine::STATUS_INSTALLED,
        ];

        yield [
            ['installed' => 1, 'active' => 1],
            ModuleStateMachine::STATUS_ENABLED__MOBILE_DISABLED,
        ];

        yield [
            ['installed' => 1, 'active_on_mobile' => 0],
            ModuleStateMachine::STATUS_INSTALLED,
        ];

        yield [
            ['installed' => 1, 'active_on_mobile' => 1],
            ModuleStateMachine::STATUS_DISABLED__MOBILE_ENABLED,
        ];

        yield [
            ['installed' => 1, 'active' => 0, 'active_on_mobile' => 1],
            ModuleStateMachine::STATUS_DISABLED__MOBILE_ENABLED,
        ];

        yield [
            ['installed' => 1, 'active' => 1, 'active_on_mobile' => 0],
            ModuleStateMachine::STATUS_ENABLED__MOBILE_DISABLED,
        ];

        yield [
            ['installed' => 1, 'active' => 1, 'active_on_mobile' => 1],
            ModuleStateMachine::STATUS_ENABLED__MOBILE_ENABLED,
        ];
    }

    private function getModule(?array $dbAttributes = null): Module
    {
        return new Module(null, null, $dbAttributes);
    }
}
