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

use PrestaShop\Module\Mbo\Addons\Toolbar;
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;
use PrestaShop\Module\Mbo\Service\View\ContextBuilder;
use PrestaShop\PrestaShop\Core\Security\Permission;
use PrestaShop\PsAccountsInstaller\Installer\Facade\PsAccounts;
use PrestaShop\PsAccountsInstaller\Installer\Installer;
use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use PrestaShopBundle\Security\Attribute\AdminSecurity;
use Symfony\Component\HttpFoundation\Response;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Responsible of "Improve > Modules > Modules Catalog" page display.
 */
class ModuleCatalogController extends PrestaShopAdminController
{
    public const CONTROLLER_NAME = 'ADMINMODULESSF';

    /**
     * @var Toolbar
     */
    private $addonsToolbar;

    /**
     * @var ContextBuilder
     */
    private $contextBuilder;

    /**
     * @var PsAccounts
     */
    private $psAccountsFacade;

    /**
     * @var Installer
     */
    private $psAccountsInstaller;

    public function __construct(
        Toolbar $addonsToolbar,
        ContextBuilder $contextBuilder,
        PsAccounts $psAccountsFacade,
        Installer $psAccountsInstaller,
    ) {
        $this->addonsToolbar = $addonsToolbar;
        $this->contextBuilder = $contextBuilder;
        $this->psAccountsFacade = $psAccountsFacade;
        $this->psAccountsInstaller = $psAccountsInstaller;
    }

    #[AdminSecurity(
        "is_granted('read', request.get('_legacy_controller')) && is_granted('update', request.get('_legacy_controller')) && is_granted('create', request.get('_legacy_controller')) && is_granted('delete', request.get('_legacy_controller'))"
    )]
    public function indexAction(): Response
    {
        $extraParams = \ps_mbo::getCdcMediaUrl();

        /*********************
         * PrestaShop Account *
         * *******************/
        $urlAccountsCdn = '';

        try {
            $accountsService = $this->psAccountsFacade->getPsAccountsService();
        } catch (\PrestaShop\PsAccountsInstaller\Installer\Exception\InstallerException $e) {
            // Seems the module is not here, try to install it
            $this->psAccountsInstaller->install();
            try {
                $accountsService = $this->psAccountsFacade->getPsAccountsService();
            } catch (\Exception $e) {
                // Installation seems to not work properly
                $accountsService = null;
                ErrorHelper::reportError($e);
            }
        }

        if (null !== $accountsService) {
            try {
                \Media::addJsDef([
                    'contextPsAccounts' => $this->psAccountsFacade->getPsAccountsPresenter()
                        ->present('ps_mbo'),
                ]);

                // Retrieve the PrestaShop Account CDN
                $urlAccountsCdn = $accountsService->getAccountsCdn();
            } catch (\Exception $e) {
                ErrorHelper::reportError($e);
            }
        }

        return $this->render(
            '@Modules/ps_mbo/views/templates/admin/controllers/module_catalog/catalog.html.twig',
            [
                'layoutHeaderToolbarBtn' => $this->addonsToolbar->getToolbarButtons(),
                'layoutTitle' => $this->trans('Marketplace', [], 'Modules.Mbo.Modulescatalog'),
                'requireAddonsSearch' => true,
                'requireBulkActions' => false,
                'showContentHeader' => true,
                'enableSidebar' => true,
                'help_link' => $this->generateSidebarLink('AdminModules'),
                'requireFilterStatus' => false,
                'level' => $this->authorizationLevel(static::CONTROLLER_NAME),
                'shop_context' => $this->contextBuilder->getViewContext(),
                'urlAccountsCdn' => $urlAccountsCdn,
                'errorMessage' => $this->trans(
                    'You do not have permission to add this.',
                    [],
                    'Admin.Notifications.Error'
                ),
            ] + $extraParams
        );
    }

    /**
     * Responsible for displaying error block when CDC cannot be loaded.
     *
     * @return Response
     */
    public function cdcErrorAction(): Response
    {
        return $this->render(
            '@Modules/ps_mbo/views/templates/admin/controllers/module_catalog/cdc-error.html.twig'
        );
    }

    /**
     * Checks if the attributes are granted against the current authentication token and optionally supplied object.
     *
     * @param string $controller name of the controller that token is tested against
     *
     * @return int
     *
     * @throws \LogicException
     */
    private function authorizationLevel($controller)
    {
        if ($this->isGranted(Permission::DELETE, $controller)) {
            return Permission::LEVEL_DELETE;
        }

        if ($this->isGranted(Permission::CREATE, $controller)) {
            return Permission::LEVEL_CREATE;
        }

        if ($this->isGranted(Permission::UPDATE, $controller)) {
            return Permission::LEVEL_UPDATE;
        }

        if ($this->isGranted(Permission::READ, $controller)) {
            return Permission::LEVEL_READ;
        }

        return 0;
    }
}
