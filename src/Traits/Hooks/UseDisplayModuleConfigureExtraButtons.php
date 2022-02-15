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

trait UseDisplayModuleConfigureExtraButtons
{
    /**
     * @return void
     *
     * @throws \Exception
     */
    public function bootUseDisplayModuleConfigureExtraButtons(): void
    {
        if (method_exists($this, 'addAdminControllerMedia')) {
            $this->addAdminControllerMedia('loadMediaForModuleConfigureExtraButtons');
        }
    }

    /**
     * Hook displayModuleConfigureExtraButtons.
     * Add additional buttons on the module configure page's toolbar.
     *
     * @return string
     */
    public function hookDisplayModuleConfigureExtraButtons(array $params): string
    {
        $this->smarty->assign([
            'configure_toolbar_extra_buttons' => [
                [
                    'class' => 'btn-primary',
                    'id' => 'mbo-desc-module-update',
                    'title' => 'Check for updates',
                    'url' => '#',
                    'icon' => 'process-icon-refresh',
                    'data_attributes' => [
                        'module-name' => $params['name'],
                        'target' => $this->get('router')->generate('admin_mbo_addons_module_upgrade'),
                    ],
                ],
            ],
        ]);

        return $this->fetch('module:ps_mbo/views/templates/hook/configure-toolbar.tpl');
    }

    /**
     * Add JS and CSS file
     *
     * @see \PrestaShop\Module\Mbo\Traits\Hooks\UseAdminControllerSetMedia
     *
     * @return void
     */
    protected function loadMediaForModuleConfigureExtraButtons(): void
    {
        if (\Tools::getValue('controller') === 'AdminModule') {
            $this->context->controller->addJs($this->getPathUri() . 'views/js/module-configure-extra-buttons.js?v=' . $this->version);
        }
    }
}
