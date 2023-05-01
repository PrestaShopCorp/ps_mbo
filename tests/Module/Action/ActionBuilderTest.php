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

namespace PrestaShop\Module\Mbo\Tests\Module\Action;

use PHPUnit\Framework\TestCase;
use PrestaShop\Module\Mbo\Api\Security\AdminAuthenticationProvider;
use PrestaShop\Module\Mbo\Distribution\Client;
use PrestaShop\Module\Mbo\Module\Action\ActionBuilder;
use PrestaShop\Module\Mbo\Module\Action\ActionInterface;
use PrestaShop\Module\Mbo\Module\Action\ActionRetriever;
use PrestaShop\Module\Mbo\Module\Action\DisableAction;
use PrestaShop\Module\Mbo\Module\Action\DisableMobileAction;
use PrestaShop\Module\Mbo\Module\Action\EnableAction;
use PrestaShop\Module\Mbo\Module\Action\EnableMobileAction;
use PrestaShop\Module\Mbo\Module\Action\InstallAction;
use PrestaShop\Module\Mbo\Module\Action\ResetAction;
use PrestaShop\Module\Mbo\Module\Action\Scheduler;
use PrestaShop\Module\Mbo\Module\Action\UninstallAction;
use PrestaShop\Module\Mbo\Module\Action\UpgradeAction;
use PrestaShop\Module\Mbo\Module\Repository;
use PrestaShop\PrestaShop\Core\Module\ModuleManager;

class ActionBuilderTest extends TestCase
{
    private $moduleManager;

    private $distributionApi;

    private $repository;

    private $adminAuthenticationProvider;

    protected function setUp(): void
    {
        $this->moduleManager = $this->createMock(ModuleManager::class);
        $this->distributionApi = $this->createMock(Client::class);
        $this->repository = $this->createMock(Repository::class);
        $this->adminAuthenticationProvider = $this->createMock(AdminAuthenticationProvider::class);
    }

    public function testBuildThrowsExceptionWhenActionIsUndefined()
    {
        $actionBuilder = new ActionBuilder($this->moduleManager, $this->repository, $this->distributionApi, $this->adminAuthenticationProvider);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Action definition requirements are not met : action name parameter cannot be empty');
        $actionBuilder->build([
            'module_name' => 'fake_module',
        ]);
    }

    public function testBuildThrowsExceptionWhenActionIsUnknown()
    {
        $actionBuilder = new ActionBuilder($this->moduleManager, $this->repository, $this->distributionApi, $this->adminAuthenticationProvider);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unrecognized module action name');
        $actionBuilder->build([
            'action' => 'fake_action',
            'module_name' => 'fake_module',
        ]);
    }

    public function testBuildThrowsExceptionWhenModuleNameIsUndefined()
    {
        $actionBuilder = new ActionBuilder($this->moduleManager, $this->repository, $this->distributionApi, $this->adminAuthenticationProvider);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Action definition requirements are not met : module_name cannot be empty');
        $actionBuilder->build([
            'action' => ActionBuilder::ACTION_NAME_INSTALL,
        ]);
    }

    /**
     * @dataProvider getActionDefinitions
     */
    public function testBuild(array $actionData, string $expectedClass)
    {
        $actionBuilder = new ActionBuilder($this->moduleManager, $this->repository, $this->distributionApi, $this->adminAuthenticationProvider);
        $this->assertInstanceOf($expectedClass, $actionBuilder->build($actionData));
    }

    public function getActionDefinitions()
    {
        return [
            [['action' => ActionBuilder::ACTION_NAME_INSTALL, 'module_name' => 'fake_module', 'source' => 'toto'], InstallAction::class],
            [['action' => ActionBuilder::ACTION_NAME_UNINSTALL, 'module_name' => 'fake_module', 'source' => 'toto'], UninstallAction::class],
            [['action' => ActionBuilder::ACTION_NAME_UPGRADE, 'module_name' => 'fake_module', 'source' => 'toto'], UpgradeAction::class],
            [['action' => ActionBuilder::ACTION_NAME_ENABLE, 'module_name' => 'fake_module', 'source' => 'toto'], EnableAction::class],
            [['action' => ActionBuilder::ACTION_NAME_DISABLE, 'module_name' => 'fake_module', 'source' => 'toto'], DisableAction::class],
            [['action' => ActionBuilder::ACTION_NAME_ENABLE_MOBILE, 'module_name' => 'fake_module', 'source' => 'toto'], EnableMobileAction::class],
            [['action' => ActionBuilder::ACTION_NAME_DISABLE_MOBILE, 'module_name' => 'fake_module', 'source' => 'toto'], DisableMobileAction::class],
            [['action' => ActionBuilder::ACTION_NAME_RESET, 'module_name' => 'fake_module', 'source' => 'toto'], ResetAction::class],
        ];
    }
}
