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

use ToolsCore as Tools;

trait UseDashboardZoneThree
{
    /**
     * @return void
     *
     * @throws \Exception
     */
    public function bootUseDashboardZoneThree(): void
    {
        if (method_exists($this, 'addAdminControllerMedia')) {
            $this->addAdminControllerMedia('loadMediaForDashboardColumnThree');
        }
    }

    /**
     * Display addons data & links in the third column of the dashboard
     *
     * @param array $params
     *
     * @return false|string
     */
    public function hookDashboardZoneThree(array $params)
    {
        $this->context->smarty->assign(
            [
                'shop_context' => json_encode($this->get('mbo.cdc.context_builder')->getViewContext()),
                'cdcErrorUrl' => $this->get('router')->generate('admin_mbo_module_cdc_error'),
            ]
        );

        return $this->display($this->name, 'dashboard-zone-three.tpl');
    }

    /**
     * Add JS and CSS file
     *
     * @return void
     *
     * @see \PrestaShop\Module\Mbo\Traits\Hooks\UseActionAdminControllerSetMedia
     */
    protected function loadMediaForDashboardColumnThree(): void
    {
        if (Tools::getValue('controller') === 'AdminDashboard') {
            $this->context->controller->addJs($this->getPathUri() . 'views/js/cdc-error-templating.js');
            $this->context->controller->addCss($this->getPathUri() . 'views/css/cdc-error-templating.css');

            $cdcJsFile = getenv('MBO_CDC_URL');
            if (false === $cdcJsFile || !is_string($cdcJsFile) || empty($cdcJsFile)) {
                $this->context->controller->addJs($this->getPathUri() . 'views/js/cdc-error.js');

                return;
            }

            $this->context->controller->addJs($cdcJsFile);
        }
    }
}
