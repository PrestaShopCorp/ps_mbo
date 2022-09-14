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

trait UseDashboardZoneOne
{
    /**
     * Display "Advices and updates" block on the left column of the dashboard
     *
     * @param array $params
     *
     * @return false|string
     */
    public function hookDashboardZoneOne(array $params)
    {
        $accountsFacade = $this->getService('ps_accounts.facade');
        $accountsService = $accountsFacade->getPsAccountsService();
        \Media::addJsDef([
            'contextPsAccounts' => $accountsFacade->getPsAccountsPresenter()
                ->present($this->name),
        ]);

        $this->context->smarty->assign(
            [
                'shop_context' => json_encode($this->get('mbo.cdc.context_builder')->getViewContext()),
                'urlAccountsCdn' => $accountsService->getAccountsCdn(),
            ]
        );

        return $this->display($this->name, 'dashboard-zone-one.tpl');
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
        if (\Tools::getValue('controller') === 'AdminDashboard') {
            $this->context->controller->addJs(getenv('MBO_CDC_URL'));
            $this->context->controller->addJs($this->getPathUri() . 'views/js/addons-connector.js?v=' . $this->version);
            $this->context->controller->addCSS($this->getPathUri() . 'views/css/addons-connect.css');
        }
    }

    public function useDashboardZoneOneExtraOperations()
    {
        //Update module position in Dashboard
        $query = 'SELECT id_hook FROM ' . _DB_PREFIX_ . "hook WHERE name = 'dashboardZoneOne'";

        /** @var array $result */
        $result = Db::getInstance()->ExecuteS($query);
        $id_hook = $result['0']['id_hook'];

        $this->updatePosition((int) $id_hook, false);
    }
}
