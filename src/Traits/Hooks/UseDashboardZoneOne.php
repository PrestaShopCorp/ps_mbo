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

use Db;
use Exception;
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;
use PrestaShop\Module\Mbo\Traits\HaveCdcComponent;
use PrestaShopDatabaseException;

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
        return $this->smartyDisplayTpl('dashboard-zone-one.tpl', [
            'urlAccountsCdn' => $this->loadPsAccounts(),
        ]);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function bootUseDashboardZoneOne(): void
    {
        if (method_exists($this, 'addAdminControllerMedia')) {
            $this->addAdminControllerMedia('loadMediaDashboardZoneOne');
        }
    }

    /**
     * Add JS and CSS file
     *
     * @see \PrestaShop\Module\Mbo\Traits\Hooks\UseActionAdminControllerSetMedia
     *
     * @return void
     */
    protected function loadMediaDashboardZoneOne(): void
    {
        $this->loadCdcMediaFilesForControllers(['AdminDashboard']);
    }

    /**
     * @throws PrestaShopDatabaseException
     */
    public function useDashboardZoneOneExtraOperations()
    {
        //Update module position in Dashboard
        $query = 'SELECT id_hook FROM ' . _DB_PREFIX_ . "hook WHERE name = 'dashboardZoneOne'";

        /** @var array $result */
        $result = Db::getInstance()->ExecuteS($query);
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
            $accountsFacade = $this->get('mbo.ps_accounts.facade');
            $accountsService = $accountsFacade->getPsAccountsService();
        } catch (\PrestaShop\PsAccountsInstaller\Installer\Exception\InstallerException $e) {
            ErrorHelper::reportError($e);
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
