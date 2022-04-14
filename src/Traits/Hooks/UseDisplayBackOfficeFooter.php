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

use PrestaShop\Module\Mbo\Addons\Provider\LinksProvider;
use PrestaShop\Module\Mbo\Controller\Admin\ModuleCatalogController;

trait UseDisplayBackOfficeFooter
{
    /**
     * Hook displayBackOfficeFooter.
     * Adds content in BackOffice footer section
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayBackOfficeFooter(array $params): string
    {
        /** @var LinksProvider $linksProvider */
        $linksProvider = $this->get('mbo.addons.links_provider');

        return $this->get('twig')->render(
            '@Modules/ps_mbo/views/templates/admin/controllers/module_catalog/includes/modal_addons_connect.html.twig', [
                'signUpLink' => $linksProvider->getSignUpLink(),
                'passwordForgottenLink' => $linksProvider->getPasswordForgottenLink(),
                'level' => $this->get('mbo.security.permission_checker')->getAuthorizationLevel(ModuleCatalogController::CONTROLLER_NAME),
                'errorMessage' => $this->trans(
                    'You do not have permission to add this.',
                    [],
                    'Admin.Notifications.Error'
                ),
            ]
        );
    }
}
