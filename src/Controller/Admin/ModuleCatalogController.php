<?php
/**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\Mbo\Controller\Admin;

use PrestaShopBundle\Controller\Admin\Improve\Modules\ModuleAbstractController;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use Symfony\Component\HttpFoundation\Response;

/**
 * Responsible of "Improve > Modules > Modules Catalog" page display.
 */
class ModuleCatalogController extends ModuleAbstractController
{
    /**
     * Module Catalog page
     *
     * @AdminSecurity("is_granted(['read', 'create', 'update', 'delete'], 'ADMINMODULESSF_')")
     *
     * @return Response
     */
    public function indexAction()
    {
        return $this->render(
            '@PrestaShop/Admin/Module/catalog.html.twig',
            [
                'layoutHeaderToolbarBtn' => $this->getToolbarButtons(),
                'layoutTitle' => $this->trans('Modules catalog', 'Admin.Navigation.Menu'),
                'requireAddonsSearch' => true,
                'requireBulkActions' => false,
                'showContentHeader' => true,
                'enableSidebar' => true,
                'help_link' => $this->generateSidebarLink('AdminModules'),
                'requireFilterStatus' => false,
                'level' => $this->authorizationLevel(self::CONTROLLER_NAME),
                'errorMessage' => $this->trans(
                    'You do not have permission to add this.',
                    'Admin.Notifications.Error'
                ),
            ]
        );
    }
}
