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
use Tab;
use Tools;

trait UseDispatcherBefore
{
    /**
     * Hook actionDispatcherBefore.
     *
     * @throws EmployeeException
     */
    public function hookActionDispatcherBefore(array $params): void
    {
        // Whatever the call in the backoffice, we check if the MBO API user exists
        if (
            \Dispatcher::FC_ADMIN == (int) $params['controller_type'] ||
            Tools::getValue('controller') === 'apiPsMbo'
        ) {
            $apiUser = $this->getAdminAuthenticationProvider()->ensureApiUserExistence();
        }

        if (Tools::getValue('controller') !== 'apiPsMbo') {
            return;
        }

        if (!$apiUser->isLoggedBack()) { // Log the user
            $idTab = Tab::getIdFromClassName('apiPsMbo');
            $token = Tools::getAdminToken('apiPsMbo' . (int) $idTab . (int) $apiUser->id);
            $cookie = $this->getAdminAuthenticationProvider()->apiUserLogin($apiUser);

            Cache::clean('isLoggedBack' . $apiUser->id);

            $this->context->employee = $apiUser;
            $this->context->cookie = $cookie;
            Context::getContext()->cookie = $cookie;
        }
    }
}
