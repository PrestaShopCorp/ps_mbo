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

namespace PrestaShop\Module\Mbo\Controller\Admin;

use PrestaShopBundle\Controller\Admin\Improve\Modules\ModuleAbstractController;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use Symfony\Component\HttpFoundation\Response;

/**
 * Responsible of "Improve > Modules > Modules Catalog" page display.
 */
class ModuleCatalogController extends ModuleAbstractController
{
    public const CONTROLLER_NAME = 'ADMINMODULESSF';

    /**
     * Module Catalog page
     *
     * @AdminSecurity(
     *     "is_granted('read', request.get('_legacy_controller')) && is_granted('update', request.get('_legacy_controller')) && is_granted('create', request.get('_legacy_controller')) && is_granted('delete', request.get('_legacy_controller'))",
     *     message="You do not have permission to update this.",
     *     redirectRoute="admin_administration"
     * )
     *
     * @return Response
     */
    public function indexAction(): Response
    {
        $moduleUri = __PS_BASE_URI__ . 'modules/ps_mbo/';

        $extraParams = [
            'cdc_error_templating_url' => $moduleUri . 'views/js/cdc-error-templating.js',
            'cdc_error_templating_css' => $moduleUri . 'views/css/cdc-error-templating.css',
        ];

        $cdcJsFile = getenv('MBO_CDC_URL');
        if (false === $cdcJsFile || !is_string($cdcJsFile) || empty($cdcJsFile)) {
            $extraParams['cdc_script_not_found'] = true;
            $extraParams['cdc_error_url'] = $moduleUri . 'views/js/cdc-error.js';
        } else {
            $extraParams['cdc_url'] = $cdcJsFile;
        }

        return $this->render(
            '@Modules/ps_mbo/views/templates/admin/controllers/module_catalog/catalog.html.twig',
            [
                'layoutHeaderToolbarBtn' => $this->get('mbo.addons.toolbar')->getToolbarButtons(),
                'layoutTitle' => $this->trans('Marketplace', 'Modules.Mbo.Modulescatalog'),
                'requireAddonsSearch' => true,
                'requireBulkActions' => false,
                'showContentHeader' => true,
                'enableSidebar' => true,
                'help_link' => $this->generateSidebarLink('AdminModules'),
                'requireFilterStatus' => false,
                'level' => $this->authorizationLevel(static::CONTROLLER_NAME),
                'shop_context' => $this->get('mbo.cdc.context_builder')->getViewContext(),
                'errorMessage' => $this->trans(
                    'You do not have permission to add this.',
                    'Admin.Notifications.Error'
                ),
            ] + $extraParams
        );
    }

    /**
     * Responsible for displaying the data inside the modal when user clicks on "See more" on module card
     *
     * @param int $moduleId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function seeMoreAction(int $moduleId): Response
    {
        return $this->render(
            '@Modules/ps_mbo/views/templates/admin/controllers/module_catalog/see-more.html.twig',
            [
                'shop_context' => $this->get('mbo.cdc.context_builder')->getViewContext(),
                'module' => [
                    'attributes' => [
                        'id' => $moduleId,
                    ],
                ],
            ]
        );
    }

    /**
     * Responsible for displaying error block when CDC cannot be loaded.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cdcErrorAction(): Response
    {
        return $this->render(
            '@Modules/ps_mbo/views/templates/admin/controllers/module_catalog/cdc-error.html.twig'
        );
    }
}
