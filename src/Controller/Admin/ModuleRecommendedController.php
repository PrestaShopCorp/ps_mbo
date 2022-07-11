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

use PrestaShop\Module\Mbo\Addons\Provider\LinksProvider;
use PrestaShop\Module\Mbo\RecommendedModule\RecommendedModulePresenterInterface;
use PrestaShop\Module\Mbo\Service\View\ContextBuilder;
use PrestaShop\Module\Mbo\Tab\TabCollectionProviderInterface;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Responsible of render json data for ajax display of Recommended Modules.
 */
class ModuleRecommendedController extends FrameworkBundleAdminController
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var TabCollectionProviderInterface
     */
    protected $tabCollectionProvider;

    /**
     * @var RecommendedModulePresenterInterface
     */
    protected $recommendedModulePresenter;

    /**
     * @var LinksProvider
     */
    protected $linksProvider;

    /**
     * @param RequestStack $requestStack
     * @param TabCollectionProviderInterface $tabCollectionProvider
     * @param RecommendedModulePresenterInterface $recommendedModulePresenter
     * @param LinksProvider $linksProvider
     */
    public function __construct(
        RequestStack $requestStack,
        TabCollectionProviderInterface $tabCollectionProvider,
        RecommendedModulePresenterInterface $recommendedModulePresenter,
        LinksProvider $linksProvider,
        ContextBuilder $cdcContextBuilder
    ) {
        parent::__construct();
        $this->requestStack = $requestStack;
        $this->tabCollectionProvider = $tabCollectionProvider;
        $this->recommendedModulePresenter = $recommendedModulePresenter;
        $this->linksProvider = $linksProvider;
        $this->cdcContextBuilder = $cdcContextBuilder;
    }

    /**
     * @return JsonResponse
     */
    public function indexAction(): JsonResponse
    {
        $response = new JsonResponse();
        try {
            $tabCollection = $this->tabCollectionProvider->getTabCollection();
            $tabClassName = $this->requestStack->getCurrentRequest()->get('tabClassName');
            $tab = $tabCollection->getTab($tabClassName);
            $response->setData([
                'content' => $this->renderView(
                    '@Modules/ps_mbo/views/templates/admin/controllers/module_catalog/recommended-modules.html.twig',
                    [
                        'shop_context' => $this->cdcContextBuilder->getRecommendedModulesContext($tab),
                        'recommendedModulesInstalled' => $this->recommendedModulePresenter->presentCollection($tab->getRecommendedModulesInstalled()),
                        'recommendedModulesNotInstalled' => $this->recommendedModulePresenter->presentCollection($tab->getRecommendedModulesNotInstalled()),
                        'linkUrl' => $this->linksProvider->getAddonsLinkByControllerName($tabClassName, 'dispatch'),
                    ]
                ),
            ]);
        } catch (ServiceUnavailableHttpException $exception) {
            $response->setData([
                'content' => $this->renderView('@Modules/ps_mbo/views/templates/admin/error.html.twig'),
            ]);
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->add($exception->getHeaders());
        }

        return $response;
    }
}
