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

use Exception;
use LogicException;
use PrestaShop\Module\Mbo\Addons\Toolbar;
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;
use PrestaShop\Module\Mbo\Service\View\ContextBuilder;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShop\PrestaShop\Core\Help\Documentation;
use PrestaShop\PrestaShop\Core\Module\ModuleManager;
use PrestaShop\PrestaShop\Core\Security\Permission;
use PrestaShop\PsAccountsInstaller\Installer\Facade\PsAccounts;
use PrestaShop\PsAccountsInstaller\Installer\Installer;
use PrestaShopBundle\Controller\Admin\Improve\Modules\ModuleAbstractController;
use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

/**
 * Responsible of "Improve > Modules > Modules Catalog" page display.
 */
class ModuleCatalogController extends PrestaShopAdminController
{
    public const CONTROLLER_NAME = 'ADMINMODULESSF';
    private Toolbar $addonsToolbar;
    private ContextBuilder $contextBuilder;
    private ModuleManager $moduleManager;
    private PsAccounts $psAccountsFacade;
    private Installer $psAccountsInstaller;
    private LegacyContext $legacyContext;
    private Documentation $documentation;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        Toolbar $addonsToolbar,
        ContextBuilder $contextBuilder,
        ModuleManager $moduleManager,
        PsAccounts $psAccountsFacade,
        Installer $psAccountsInstaller,
        LegacyContext $legacyContext,
        Documentation $documentation,
        AuthorizationCheckerInterface $authorizationChecker
    )
    {
        $this->addonsToolbar = $addonsToolbar;
        $this->contextBuilder = $contextBuilder;
        $this->moduleManager = $moduleManager;
        $this->psAccountsFacade = $psAccountsFacade;
        $this->psAccountsInstaller = $psAccountsInstaller;
        $this->legacyContext = $legacyContext;
        $this->documentation = $documentation;
        $this->authorizationChecker = $authorizationChecker;
    }

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

        /*********************
         * PrestaShop Account *
         * *******************/
        $urlAccountsCdn = '';

        try {
            $accountsService = $this->psAccountsFacade->getPsAccountsService();
            if ($this->ensurePsAccountIsEnabled()) $this->ensurePsEventbusEnabled();
        } catch (\PrestaShop\PsAccountsInstaller\Installer\Exception\InstallerException $e) {
            // Seems the module is not here, try to install it
            $this->psAccountsInstaller->install();
            try {
                $accountsService = $this->psAccountsFacade->getPsAccountsService();
            } catch (Exception $e) {
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
            } catch (Exception $e) {
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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cdcErrorAction(): Response
    {
        return $this->render(
            '@Modules/ps_mbo/views/templates/admin/controllers/module_catalog/cdc-error.html.twig'
        );
    }

    private function ensurePsAccountIsEnabled(): bool
    {
        if (!$this->psAccountsInstaller) return false;

        $accountsEnabled = $this->psAccountsInstaller->isModuleEnabled();
        if ($accountsEnabled) return true;

        $moduleManager = $this->get('PrestaShop\PrestaShop\Core\Module\ModuleManager');
        return $moduleManager->enable($this->psAccountsInstaller->getModuleName());
    }

    private function ensurePsEventbusEnabled()
    {
        $installer = $this->get('mbo.ps_eventbus.installer');
        if ($installer->install()) {
            $installer->enable();
        }
    }

    /**
     * Generates a documentation link.
     *
     * @param string $section Legacy controller name
     * @param bool|string $title Help title
     *
     * @return string
     */
    private function generateSidebarLink($section, $title = false)
    {
        if (empty($title)) {
            $title = $this->trans('Help', [], 'Admin.Global');
        }

        $iso = (string) $this->legacyContext->getEmployeeLanguageIso();

        $url = $this->generateUrl('admin_common_sidebar', [
            'url' => $this->documentation->generateLink($section, $iso),
            'title' => $title,
        ]);

        // this line is allow to revert a new behaviour introduce in sf 5.4 which break the result we used to have
        return strtr($url, ['%2F' => '%252F']);
    }

    /**
     * Checks if the attributes are granted against the current authentication token and optionally supplied object.
     *
     * @param string $controller name of the controller that token is tested against
     *
     * @return int
     *
     * @throws LogicException
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
