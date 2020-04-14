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

use PrestaShop\Module\Mbo\AddonsSelectionLinkProvider;
use PrestaShop\Module\Mbo\ExternalContentProvider\ExternalContentProvider;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Templating\EngineInterface;

/**
 * Responsible of "Improve > Design > Themes Catalog" page display.
 */
class ThemeCatalogController extends FrameworkBundleAdminController
{
    /**
     * @AdminSecurity("is_granted('read', request.get('_legacy_controller'))")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $response = new Response();
        /** @var EngineInterface $templateEngine */
        $templateEngine = $this->get('twig');

        try {
            $externalContentProvider = new ExternalContentProvider();
            /** @var AddonsSelectionLinkProvider $addonsSelectionLinkProvider */
            $addonsSelectionLinkProvider = $this->get('mbo.addons_selection_link_provider');

            $content = $templateEngine->render(
                '@Modules/ps_mbo/views/templates/admin/controllers/theme_catalog/addons_store.html.twig',
                [
                    'pageContent' => $externalContentProvider->getContent($addonsSelectionLinkProvider->getLinkUrl()),
                    'layoutHeaderToolbarBtn' => [],
                    'layoutTitle' => $this->trans('Themes Catalog', 'Admin.Navigation.Menu'),
                    'requireAddonsSearch' => true,
                    'requireBulkActions' => false,
                    'showContentHeader' => true,
                    'enableSidebar' => true,
                    'help_link' => $this->generateSidebarLink($request->attributes->get('_legacy_controller')),
                    'requireFilterStatus' => false,
                    'level' => $this->authorizationLevel($request->attributes->get('_legacy_controller')),
                ]
            );
            $response->setContent($content);
        } catch (ServiceUnavailableHttpException $exception) {
            $response->setContent($templateEngine->render('@Modules/ps_mbo/views/templates/admin/error.html.twig'));
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->add($exception->getHeaders());
        }

        return $response;
    }
}
