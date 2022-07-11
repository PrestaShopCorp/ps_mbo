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
use PrestaShop\Module\Mbo\Addons\Toolbar;
use PrestaShop\Module\Mbo\Module\Collection;
use PrestaShop\Module\Mbo\Module\ModuleBuilderInterface;
use PrestaShop\Module\Mbo\Module\Presenter\ModulesForListingPresenter;
use PrestaShop\Module\Mbo\Module\Query\GetModulesForListing;
use PrestaShop\Module\Mbo\Module\RepositoryInterface;
use PrestaShop\Module\Mbo\Service\View\ContextBuilder;
use PrestaShop\PrestaShop\Adapter\Presenter\Module\ModulePresenter;
use PrestaShopBundle\Controller\Admin\Improve\Modules\ModuleAbstractController;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Responsible of "Improve > Modules > Modules Catalog" page display.
 */
class ModuleCatalogController extends ModuleAbstractController
{
    public const CONTROLLER_NAME = 'ADMINMODULESSF';

    /**
     * @var Toolbar
     */
    private $toolbar;

    /**
     * @var RepositoryInterface
     */
    private $moduleRepository;

    /**
     * @var ModuleBuilderInterface
     */
    private $moduleBuilder;

    /**
     * @var ModulePresenter
     */
    private $modulePresenter;
    /**
     * @var ModulesForListingPresenter
     */
    private $modulesForListingPresenter;
    /**
     * @var ContextBuilder
     */
    private $cdcContextBuilder;

    public function __construct(
        Toolbar $toolbar,
        ModuleBuilderInterface $moduleBuilder,
        RepositoryInterface $moduleRepository,
        ModulePresenter $modulePresenter,
        ModulesForListingPresenter $modulesForListingPresenter,
        ContextBuilder $cdcContextBuilder
    ) {
        parent::__construct();
        $this->toolbar = $toolbar;
        $this->moduleBuilder = $moduleBuilder;
        $this->moduleRepository = $moduleRepository;
        $this->modulePresenter = $modulePresenter;
        $this->modulesForListingPresenter = $modulesForListingPresenter;
        $this->cdcContextBuilder = $cdcContextBuilder;
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
        return $this->render(
            '@Modules/ps_mbo/views/templates/admin/controllers/module_catalog/catalog.html.twig',
            [
                'layoutHeaderToolbarBtn' => $this->toolbar->getToolbarButtons(),
                'layoutTitle' => $this->trans('Modules catalog', 'Modules.Mbo.Modulescatalog'),
                'requireAddonsSearch' => true,
                'requireBulkActions' => false,
                'showContentHeader' => true,
                'enableSidebar' => true,
                'help_link' => $this->generateSidebarLink('AdminModules'),
                'requireFilterStatus' => false,
                'level' => $this->authorizationLevel(static::CONTROLLER_NAME),
                'cdc_url' => getenv('MBO_CDC_URL'),
                'shop_context' => $this->cdcContextBuilder->getViewContext(),
                'errorMessage' => $this->trans(
                    'You do not have permission to add this.',
                    'Admin.Notifications.Error'
                ),
            ]
        );
    }

    /**
     * Controller responsible for displaying "Catalog Module Grid" section of Module management pages with ajax.
     *
     * @AdminSecurity(
     *     "is_granted('read', request.get('_legacy_controller')) && is_granted('update', request.get('_legacy_controller')) && is_granted('create', request.get('_legacy_controller')) && is_granted('delete', request.get('_legacy_controller'))",
     *     message="You do not have permission to update this.",
     *     redirectRoute="admin_administration"
     * )
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function refreshAction(Request $request): JsonResponse
    {
        $responseArray = [];
        try {
            /** @var Collection $modules */
            $modules = $this->getQueryBus()->handle(new GetModulesForListing());
            $responseArray['domElements'] = $this->modulesForListingPresenter->present($modules);
            $responseArray['status'] = true;
        } catch (Exception $e) {
            $responseArray['msg'] = $this->trans(
                'Cannot get catalog data, please try again later. Reason: %error_details%',
                'Modules.Mbo.Modulescatalog',
                ['%error_details%' => print_r($e->getMessage(), true)]
            );
            $responseArray['status'] = false;
        }

        return new JsonResponse($responseArray);
    }

    /**
     * Responsible of displaying the data inside the modal when user clicks on "See more" on module card
     *
     * @param int $moduleId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function seeMoreAction(int $moduleId): Response
    {
        $module = $this->moduleRepository->getModuleById($moduleId);

        $this->moduleBuilder->generateAddonsUrls($module);

        return $this->render(
            '@Modules/ps_mbo/views/templates/admin/controllers/module_catalog/modal-read-more-content.html.twig',
            [
                'module' => $this->modulePresenter->present($module),
                'level' => $this->authorizationLevel(self::CONTROLLER_NAME),
            ]
        );
    }
}
