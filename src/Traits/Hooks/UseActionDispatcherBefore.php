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

namespace PrestaShop\Module\Mbo\Traits\Hooks;

use Cache;
use Configuration;
use Context;
use PrestaShop\Module\Mbo\Distribution\Config\Command\VersionChangeApplyConfigCommand;
use PrestaShop\Module\Mbo\Helpers\Config;
use PrestaShop\PrestaShop\Core\Domain\Employee\Exception\EmployeeException;
use Tools;

trait UseActionDispatcherBefore
{
    /**
     * Hook actionDispatcherBefore.
     *
     * @throws EmployeeException
     */
    public function hookActionDispatcherBefore(array $params): void
    {
        $controllerName = Tools::getValue('controller');

        $this->translateTabsIfNeeded();

        // Registration failed on install, retry it
        if (in_array($controllerName, [
            'AdminPsMboModuleParent',
            'AdminPsMboRecommended',
            'apiPsMbo',
        ])) {
            $this->ensureShopIsRegistered();
            $this->ensureShopIsUpdated();
            $this->ensureApiConfigIsApplied();
        }

        if (self::checkModuleStatus()) { // If the module is not active, config values are not set yet
            $this->ensureApiUserExistAndIsLogged($controllerName, $params);
        }
    }

    private function ensureShopIsRegistered(): void
    {
        if (!file_exists($this->moduleCacheDir . 'registerShop.lock')) {
            return;
        }
        $this->registerShop();
    }

    private function ensureShopIsUpdated(): void
    {
        if (!file_exists($this->moduleCacheDir . 'updateShop.lock')) {
            return;
        }
        $this->updateShop();
    }

    private function ensureApiConfigIsApplied(): void
    {
        $cacheProvider = $this->get('doctrine.cache.provider');
        $cacheKey = 'mbo_last_ps_version_api_config_check';

        if ($cacheProvider->contains($cacheKey)) {
            $lastCheck = $cacheProvider->fetch($cacheKey);

            $timeSinceLastCheck = (strtotime('now') - strtotime($lastCheck)) / (60 * 60);
            if ($timeSinceLastCheck < 3) { // If last check happened lss than 3hrs, do nothing
                return;
            }
        }

        if (_PS_VERSION_ === Config::getLastPsVersionApiConfig()) {
            // Config already applied for this version of PS
            return;
        }

        // Apply the config for the new PS version
        $command = new VersionChangeApplyConfigCommand(_PS_VERSION_, $this->version);
        $configCollection = $this->get('mbo.distribution.api_version_change_config_apply_handler')->handle($command);

        // Update the PS_MBO_LAST_PS_VERSION_API_CONFIG
        Configuration::updateValue('PS_MBO_LAST_PS_VERSION_API_CONFIG', _PS_VERSION_);

        $cacheProvider->save($cacheKey, (new \DateTime())->format('Y-m-d H:i:s'), 0);
    }

    /**
     * @param string|bool $controllerName
     * @param array $params
     *
     * @return void
     *
     * @throws \PrestaShop\PrestaShop\Core\Domain\Employee\Exception\EmployeeException
     * @throws \PrestaShop\PrestaShop\Core\Exception\CoreException
     */
    private function ensureApiUserExistAndIsLogged($controllerName, array $params): void
    {
        $apiUser = null;
        // Whatever the call in the MBO API, we check if the MBO API user exists
        if (\Dispatcher::FC_ADMIN == (int) $params['controller_type'] || $controllerName === 'apiPsMbo') {
            $apiUser = $this->getAdminAuthenticationProvider()->ensureApiUserExistence();
        }

        if ($controllerName !== 'apiPsMbo' || !$apiUser) {
            return;
        }

        if (!$apiUser->isLoggedBack()) { // Log the user
            $cookie = $this->getAdminAuthenticationProvider()->apiUserLogin($apiUser);

            Cache::clean('isLoggedBack' . $apiUser->id);

            $this->context->employee = $apiUser;
            $this->context->cookie = $cookie;
            Context::getContext()->cookie = $cookie;
        }
    }
}
