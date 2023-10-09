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

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Responsible of "Improve > Design > Themes Catalog" page display.
 */
class ThemeCatalogController extends FrameworkBundleAdminController
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(
        RequestStack $requestStack
    ) {
        parent::__construct();
        $this->requestStack = $requestStack;
    }

    /**
     * @AdminSecurity("is_granted('read', request.get('_legacy_controller'))")
     *
     * @return Response
     */
    public function indexAction(): Response
    {
        $response = new Response();

        try {
            $response->setContent($this->renderView(
                '@Modules/ps_mbo/views/templates/admin/controllers/theme_catalog/addons_store.html.twig',
                [
                    'pageContent' => $this->get('mbo.externalcontent.provider')->getContent(
                        $this->get('mbo.addons.links_provider')->getSelectionLink()
                    ),
                    'layoutHeaderToolbarBtn' => [],
                    'layoutTitle' => $this->trans('Themes Catalog', 'Modules.Mbo.Themescatalog'),
                    'requireAddonsSearch' => true,
                    'requireBulkActions' => false,
                    'showContentHeader' => true,
                    'enableSidebar' => true,
                    'help_link' => $this->generateSidebarLink($this->requestStack->getCurrentRequest()->get('_legacy_controller')),
                    'requireFilterStatus' => false,
                    'level' => $this->authorizationLevel($this->requestStack->getCurrentRequest()->get('_legacy_controller')),
                ]
            ));
        } catch (ServiceUnavailableHttpException $exception) {
            $response->setContent($this->renderView('@Modules/ps_mbo/views/templates/admin/error.html.twig'));
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->add($exception->getHeaders());
        }

        return $response;
    }
}
