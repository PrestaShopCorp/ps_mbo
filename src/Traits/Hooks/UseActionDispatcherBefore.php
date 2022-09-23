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
use Context;
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

        // Registration failed on install, retry it
        if (in_array($controllerName, [
            'AdminPsMboModuleParent',
            'AdminPsMboRecommended',
            'apiPsMbo',
        ])) {
            $this->ensureShopIsRegistered();
            $this->ensureShopIsUpdated();
        }

        $this->ensureApiUserExistAndIsLogged($controllerName, $params);
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
