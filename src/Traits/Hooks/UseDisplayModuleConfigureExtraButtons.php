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
use Symfony\Bundle\FrameworkBundle\Routing\Router;

trait UseDisplayModuleConfigureExtraButtons
{
    /**
     * Hook displayModuleConfigureExtraButtons.
     * Add additional buttons on the module configure page's toolbar.
     */
    public function hookDisplayModuleConfigureExtraButtons(): string
    {
        try {
            /** @var Router $router */
            $router = $this->get('router');

            if (null === $router) {
                throw new ExpectedServiceNotFoundException(
                    'Some services not found in UseDisplayModuleConfigureExtraButtons'
                );
            }
        } catch (\Exception $e) {
            ErrorHelper::reportError($e);
            return '';
        }
        $this->smarty->assign([
            'configure_toolbar_extra_buttons' => [
                [
                    'class' => 'btn-primary',
                    'title' => $this->trans('Check for updates', [], 'Modules.Mbo.Modulescatalog'),
                    'url' => $router->generate('admin_module_updates'),
                ],
            ],
        ]);

        return $this->fetch('module:ps_mbo/views/templates/hook/configure-toolbar.tpl');
    }
}
