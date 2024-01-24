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
use PrestaShop\Module\Mbo\Exception\ExpectedServiceNotFoundException;
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;
use PrestaShop\Module\Mbo\Service\View\ContextBuilder;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

trait UseDisplayAdminThemesListAfter
{
    /**
     * Hook displayAdminThemesListAfter.
     * Includes content just after the themes list.
     *
     * @throws Exception
     */
    public function hookDisplayAdminThemesListAfter(): string
    {
        try {
            /** @var ContextBuilder $contextBuilder */
            $contextBuilder = $this->get('mbo.cdc.context_builder');
            /** @var Router $router */
            $router = $this->get('router');

            if (null === $contextBuilder || null === $router) {
                throw new ExpectedServiceNotFoundException(
                    'Some services not found in UseDisplayAdminThemesListAfter'
                );
            }
        } catch (\Exception $e) {
            ErrorHelper::reportError($e);

            return '';
        }
        $context = $contextBuilder->getViewContext();
        $context['recommendation_format'] = 'card';

        $this->smarty->assign([
            'shop_context' => json_encode($context),
            'cdcErrorUrl' => $router->generate('admin_mbo_module_cdc_error'),
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
            $this->context->controller->addJs($this->getPathUri() . 'views/js/cdc-error-templating.js');
            $this->context->controller->addCss($this->getPathUri() . 'views/css/cdc-error-templating.css');

            $cdcJsFile = getenv('MBO_CDC_URL');
            if (!is_string($cdcJsFile) || empty($cdcJsFile)) {
                $this->context->controller->addJs($this->getPathUri() . 'views/js/cdc-error.js');

                return;
            }

            $this->context->controller->addJs($cdcJsFile);
        }
    }
}
