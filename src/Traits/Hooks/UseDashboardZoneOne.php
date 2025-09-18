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

use PrestaShop\Module\Mbo\Exception\ExpectedServiceNotFoundException;
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;
use PrestaShop\Module\Mbo\Service\View\ContextBuilder;
use PrestaShop\Module\Mbo\Traits\HaveCdcComponent;
use PrestaShop\PsAccountsInstaller\Installer\Facade\PsAccounts;
use PrestaShop\PsAccountsInstaller\Installer\Installer;

if (!defined('_PS_VERSION_')) {
    exit;
}

trait UseDashboardZoneOne
{
    use HaveCdcComponent;

    /**
     * Display "Advices and updates" block on the left column of the dashboard
     *
     * @return false|string
     */
    public function hookDashboardZoneOne()
    {
        $extraParams = self::getCdcMediaUrl();

        try {
            /** @var ContextBuilder|null $contextBuilder */
            $contextBuilder = $this->get('mbo.cdc.context_builder');

            if (null === $contextBuilder) {
                throw new ExpectedServiceNotFoundException('Some services not found in HaveCdcComponent');
            }
        } catch (\Exception $e) {
            ErrorHelper::reportError($e);

            return '';
        }

        return $this->smartyDisplayTpl('dashboard-zone-one.tpl', [
            'urlAccountsCdn' => $this->loadPsAccounts(),
            'shop_context' => json_encode($contextBuilder->getViewContext()),
        ] + $extraParams);
    }

    /**
     * @throws \PrestaShopDatabaseException
     */
    public function useDashboardZoneOneExtraOperations(): void
    {
        // Update module position in Dashboard
        $query = 'SELECT id_hook FROM ' . _DB_PREFIX_ . "hook WHERE name = 'dashboardZoneOne'";

        /** @var array $result */
        $result = \Db::getInstance()->ExecuteS($query);
        $id_hook = $result['0']['id_hook'];

        $this->updatePosition((int) $id_hook, false);
    }

    protected function loadPsAccounts(): string
    {
        /*********************
         * PrestaShop Account *
         * *******************/
        $urlAccountsCdn = '';
        $accountsFacade = $accountsService = null;

        try {
            /** @var PsAccounts|null $accountsFacade */
            $accountsFacade = $this->get(PsAccounts::class);
            $accountsService = $accountsFacade->getPsAccountsService();
        } catch (\PrestaShop\PsAccountsInstaller\Installer\Exception\InstallerException $e) {
            /** @var Installer|null $accountsInstaller */
            $accountsInstaller = $this->get(Installer::class);
            if ($accountsInstaller) {
                // Seems the module is not here, try to install it
                $accountsInstaller->install();
                /** @var PsAccounts|null $accountsFacade */
                $accountsFacade = $this->get(PsAccounts::class);
                if ($accountsFacade) {
                    try {
                        $accountsService = $accountsFacade->getPsAccountsService();
                    } catch (\Exception $e) {
                        // Installation seems to not work properly
                        $accountsService = $accountsFacade = null;
                        ErrorHelper::reportError($e);
                    }
                }
            }
        }

        if (null !== $accountsFacade && null !== $accountsService) {
            try {
                \Media::addJsDef([
                    'contextPsAccounts' => $accountsFacade->getPsAccountsPresenter()
                        ->present('ps_mbo'),
                ]);

                // Retrieve the PrestaShop Account CDN
                $urlAccountsCdn = $accountsService->getAccountsCdn();
            } catch (\Exception $e) {
                ErrorHelper::reportError($e);
            }
        }

        return $urlAccountsCdn;
    }
}
