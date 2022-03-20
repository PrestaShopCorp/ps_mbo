<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
declare(strict_types=1);

namespace PrestaShop\Module\Mbo\Module\Presenter;

use PrestaShop\Module\Mbo\Controller\Admin\ModuleCatalogController;
use PrestaShop\Module\Mbo\Module\Collection;
use PrestaShop\Module\Mbo\Security\PermissionCheckerInterface;
use PrestaShop\PrestaShop\Adapter\Presenter\Module\ModulePresenter;
use PrestaShop\PrestaShop\Adapter\Presenter\PresenterInterface;
use PrestaShopBundle\Service\DataProvider\Admin\CategoriesProvider;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class ModulesForListingPresenter implements PresenterInterface
{
    /**
     * @var ModulePresenter
     */
    private $modulePresenter;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var PermissionCheckerInterface
     */
    private $permissionChecker;

    /**
     * @var CategoriesProvider
     */
    private $categoriesProvider;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        ModulePresenter $modulePresenter,
        Environment $twig,
        TranslatorInterface $translator,
        PermissionCheckerInterface $permissionChecker,
        CategoriesProvider $categoriesProvider
    ) {
        $this->modulePresenter = $modulePresenter;
        $this->twig = $twig;
        $this->translator = $translator;
        $this->permissionChecker = $permissionChecker;
        $this->categoriesProvider = $categoriesProvider;
    }

    /**
     * @param Collection $modules
     *
     * @return array
     */
    public function present($modules): array
    {
        $categories = $this->presentCategories($this->getCategories($modules));

        $response[] = $this->constructJsonCatalogCategoriesMenuResponse($categories);
        $response[] = $this->constructJsonCatalogBodyResponse(
            $categories,
            $modules
        );

        return $response;
    }

    /**
     * Get categories and its modules.
     *
     * @param array $categories
     *
     * @return array
     */
    protected function presentCategories(array $categories): array
    {
        foreach ($categories['categories']->subMenu as $category) {
            $category->modules = $this->modulePresenter
                ->presentCollection($category->modules);
        }

        return $categories;
    }

    /**
     * Build template for the categories dropdown on the header of page.
     *
     * @param array $categories
     *
     * @return array
     */
    protected function constructJsonCatalogCategoriesMenuResponse(array $categories): array
    {
        return [
            'selector' => '.module-menu-item',
            'content' => $this->twig->render(
                '@Modules/ps_mbo/views/templates/admin/controllers/module_catalog/includes/dropdown_categories_catalog.html.twig',
                [
                    'topMenuData' => $categories,
                ]
            ),
        ];
    }

    /**
     * Build templade for the grid view and the info header with count of modules & sort dropdown.
     *
     * @param array $categories
     * @param Collection $modules
     *
     * @return array
     */
    protected function constructJsonCatalogBodyResponse(array $categories, Collection $modules): array
    {
        $sortingHeaderContent = $this->twig->render(
            '@Modules/ps_mbo/views/templates/admin/controllers/module_catalog/includes/sorting.html.twig',
            [
                'totalModules' => count($modules),
            ]
        );

        $gridContent = $this->twig->render(
            '@Modules/ps_mbo/views/templates/admin/controllers/module_catalog/catalog-refresh.html.twig',
            [
                'categories' => $categories['categories'],
                'level' => $this->permissionChecker->getAuthorizationLevel(ModuleCatalogController::CONTROLLER_NAME),
                'errorMessage' => $this->translator->trans(
                    'You do not have permission to add this.',
                    [],
                    'Admin.Notifications.Error'
                ),
            ]
        );

        return [
            'selector' => '.module-catalog-page',
            'content' => $sortingHeaderContent . $gridContent,
        ];
    }

    /**
     * Get categories and its modules.
     *
     * @param Collection $modules List of installed modules
     *
     * @return array
     */
    private function getCategories(Collection $modules): array
    {
        return $this->categoriesProvider->getCategoriesMenu(
            $modules->getIterator()->getArrayCopy()
        );
    }
}
