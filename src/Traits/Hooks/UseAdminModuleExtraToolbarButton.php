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

use Exception;

trait UseAdminModuleExtraToolbarButton
{
    /**
     * Hook actionAdminModuleExtraToolbarButton.
     *
     * Retrieve buttons definitions to the Modules' toolbar
     *
     * @param array $params
     *
     * @return array
     */
    public function hookActionAdminModuleExtraToolbarButton(array $params): array
    {
        return $this->get('mbo.addon.toolbar')->getConnectionToolbar();
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function bootUseAdminModuleExtraToolbarButton(): void
    {
        if (method_exists($this, 'addAdminControllerMedia')) {
            $this->addAdminControllerMedia('loadMediaModuleExtraToolbarButton');
        }
    }

    /**
     * Add JS and CSS file
     *
     * @see \PrestaShop\Module\Mbo\Traits\Hooks\UseAdminControllerSetMedia
     *
     * @return void
     */
    protected function loadMediaModuleExtraToolbarButton(): void
    {
        $this->context->controller->addJs($this->getPathUri() . 'views/js/addons-connector.js?v=' . $this->version);
    }
}
