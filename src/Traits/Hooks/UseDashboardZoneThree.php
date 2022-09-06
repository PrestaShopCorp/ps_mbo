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

use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;
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
        $moduleManagerBuilder = ModuleManagerBuilder::getInstance();
        $moduleManager = $moduleManagerBuilder->build();

        $this->context->smarty->assign(
            [
                'new_version_url' => Tools::getCurrentUrlProtocolPrefix() . _PS_API_DOMAIN_ . '/version/check_version.php?v=' . _PS_VERSION_ . '&lang=' . $this->context->language->iso_code . '&autoupgrade=' . (int) ($moduleManager->isInstalled('autoupgrade') && $moduleManager->isEnabled('autoupgrade')) . '&hosted_mode=' . (int) defined('_PS_HOST_MODE_'),
                'shop_context' => json_encode($this->get('mbo.cdc.context_builder')->getViewContext()),
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
            $this->context->controller->addJs(getenv('MBO_CDC_URL'));
        }
    }
}
