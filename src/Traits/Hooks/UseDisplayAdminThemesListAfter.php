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

trait UseDisplayAdminThemesListAfter
{
    /**
     * Hook displayAdminThemesListAfter.
     * Includes content just after the themes list.
     *
     * @param array $params
     *
     * @return string
     *
     * @throws Exception
     */
    public function hookDisplayAdminThemesListAfter(array $params): string
    {
        $context = $this->get('mbo.cdc.context_builder')->getViewContext();
        $context['recommendation_format'] = 'card';

        $this->smarty->assign([
            'shop_context' => json_encode($context),
        ]);

        return $this->fetch('module:ps_mbo/views/templates/hook/recommended-themes.tpl');
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function bootUseDisplayAdminThemesListAfter(): void
    {
        if (method_exists($this, 'addAdminControllerMedia')) {
            $this->addAdminControllerMedia('loadMediaAdminThemesListAfter');
        }
    }

    /**
     * Add JS and CSS file
     *
     * @see \PrestaShop\Module\Mbo\Traits\Hooks\UseActionAdminControllerSetMedia
     *
     * @return void
     */
    protected function loadMediaAdminThemesListAfter(): void
    {
        if (\Tools::getValue('controller') === 'AdminThemes') {
            $this->context->controller->addJs(getenv('MBO_CDC_URL'));
        }
    }
}
