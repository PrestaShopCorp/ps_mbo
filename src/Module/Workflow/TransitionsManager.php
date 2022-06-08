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
declare(strict_types=1);

namespace PrestaShop\Module\Mbo\Module\Workflow;

use Exception;
use PrestaShop\Module\Mbo\Module\Module;
use PrestaShop\PrestaShop\Core\Module\ModuleManager;

class TransitionsManager
{
    /**
     * @var ModuleManager
     */
    private $moduleManager;

    public function __construct(ModuleManager $moduleManager)
    {
        $this->moduleManager = $moduleManager;
    }

    public function uninstalledToInstalled(Module $module, string $marking, array $context): bool
    {
        return $this->install($module);
    }

    /**
     * @throws Exception
     */
    public function installedToEnabledAndMobileDisabled(Module $module, string $marking, array $context): bool
    {
        return $this->enable($module) && $this->enableOnMobile($module);
    }

    /**
     * @throws Exception
     */
    public function installedToDisabledAndMobileEnabled(Module $module, string $marking, array $context): bool
    {
        return $this->disable($module) && $this->enableOnMobile($module);
    }

    /**
     * @throws Exception
     */
    public function installedToReset(Module $module, string $marking, array $context): bool
    {
        return $this->reset($module);
    }

    public function installedToConfigured(Module $module, string $marking, array $context): bool
    {
        return true;
    }

    /**
     * @throws Exception
     */
    public function installedToUpgraded(Module $module, string $marking, array $context): bool
    {
        return $this->upgrade($module);
    }

    /**
     * @throws Exception
     */
    public function installedToUninstalled(Module $module, string $marking, array $context): bool
    {
        return $this->uninstall($module);
    }

    /**
     * @throws Exception
     */
    public function enabledAndMobileEnabledToEnabledAndMobileDisabled(Module $module, string $marking, array $context): bool
    {
        return $this->enable($module) && $this->disableOnMobile($module);
    }

    /**
     * @throws Exception
     */
    public function enabledAndMobileEnabledToDisabledAndMobileEnabled(Module $module, string $marking, array $context): bool
    {
        return $this->disable($module) && $this->enableOnMobile($module);
    }

    /**
     * @throws Exception
     */
    public function enabledAndMobileEnabledToReset(Module $module, string $marking, array $context): bool
    {
        return $this->reset($module);
    }

    /**
     * @throws Exception
     */
    public function enabledAndMobileEnabledToUpgraded(Module $module, string $marking, array $context): bool
    {
        return $this->upgrade($module);
    }

    public function enabledAndMobileEnabledToConfigured(Module $module, string $marking, array $context): bool
    {
        return true;
    }

    /**
     * @throws Exception
     */
    public function enabledAndMobileEnabledToUninstalled(Module $module, string $marking, array $context): bool
    {
        return $this->uninstall($module);
    }

    /**
     * @throws Exception
     */
    public function enabledAndMobileDisabledToInstalled(Module $module, string $marking, array $context): bool
    {
        return $this->install($module);
    }

    /**
     * @throws Exception
     */
    public function enabledAndMobileDisabledToEnabledAndMobileEnabled(Module $module, string $marking, array $context): bool
    {
        return $this->enable($module) && $this->enableOnMobile($module);
    }

    /**
     * @throws Exception
     */
    public function enabledAndMobileDisabledToReset(Module $module, string $marking, array $context): bool
    {
        return $this->reset($module);
    }

    /**
     * @throws Exception
     */
    public function enabledAndMobileDisabledToUpgraded(Module $module, string $marking, array $context): bool
    {
        return $this->upgrade($module);
    }

    public function enabledAndMobileDisabledToConfigured(Module $module, string $marking, array $context): bool
    {
        return true;
    }

    /**
     * @throws Exception
     */
    public function enabledAndMobileDisabledToUninstalled(Module $module, string $marking, array $context): bool
    {
        return $this->uninstall($module);
    }

    /**
     * @throws Exception
     */
    public function disabledAndMobileEnabledToInstalled(Module $module, string $marking, array $context): bool
    {
        return $this->install($module);
    }

    /**
     * @throws Exception
     */
    public function disabledAndMobileEnabledToEnabledAndMobileEnabled(Module $module, string $marking, array $context): bool
    {
        return $this->enable($module) && $this->enableOnMobile($module);
    }

    /**
     * @throws Exception
     */
    public function disabledAndMobileEnabledToReset(Module $module, string $marking, array $context): bool
    {
        return $this->reset($module);
    }

    /**
     * @throws Exception
     */
    public function disabledAndMobileEnabledToUpgraded(Module $module, string $marking, array $context): bool
    {
        return $this->upgrade($module);
    }

    public function disabledAndMobileEnabledToConfigured(Module $module, string $marking, array $context): bool
    {
        return true;
    }

    /**
     * @throws Exception
     */
    public function disabledAndMobileEnabledToUninstalled(Module $module, string $marking, array $context): bool
    {
        return $this->uninstall($module);

    }

    /**
     * @throws Exception
     */
    private function enable(Module $module): bool
    {
        if ($module->isActive()) {
            return true;
        }

        return $this->moduleManager->enable($module->get('name')) &&
            $module->onEnable();
    }

    /**
     * @throws Exception
     */
    private function disable(Module $module): bool
    {
        if (!$module->isActive()) {
            return true;
        }

        return $this->moduleManager->disable($module->get('name')) &&
            $module->onDisable();
    }

    /**
     * @throws Exception
     */
    private function enableOnMobile(Module $module): bool
    {
        if ($module->isMobileActive()) {
            return true;
        }

        return $this->moduleManager->enableMobile($module->get('name')) &&
            $module->onMobileEnable();
    }

    /**
     * @throws Exception
     */
    private function disableOnMobile(Module $module): bool
    {
        if (!$module->isMobileActive()) {
            return true;
        }

        return $this->moduleManager->disableMobile($module->get('name')) &&
            $module->onMobileDisable();
    }

    /**
     * @throws Exception
     */
    private function reset(Module $module): bool
    {
        return $this->moduleManager->reset($module->get('name')) &&
            $module->onReset();
    }

    /**
     * @throws Exception
     */
    private function upgrade(Module $module): bool
    {
        return $this->moduleManager->upgrade($module->get('name')) &&
            $module->onUpgrade($module->get('version'));
    }

    /**
     * @throws Exception
     */
    private function uninstall(Module $module): bool
    {
        return $this->moduleManager->uninstall($module->get('name')) &&
            $module->onUninstall();
    }

    /**
     * @throws Exception
     */
    private function install(Module $module): bool
    {
        return $this->moduleManager->install($module->get('name')) &&
            $module->onInstall();
    }
}
