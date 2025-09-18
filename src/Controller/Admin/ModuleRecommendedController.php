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

use PrestaShop\Module\Mbo\Service\View\ContextBuilder;
use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Responsible of render json data for ajax display of Recommended Modules.
 */
class ModuleRecommendedController extends PrestaShopAdminController
{
    /**
     * @return JsonResponse|RedirectResponse
     */
    public function indexAction(
        Request $request,
        ContextBuilder $contextBuilder,
    ): Response {
        $response = new JsonResponse();
        try {
            $tabClassName = $request->get('tabClassName');
            if (null === $tabClassName) { // In case the recommended modules page is requested without giving tab context, we redirect to Modules catalog page
                $routeParams = [];
                $query = \Tools::getValue('bo_query');
                if (false !== $query && !empty(trim($query))) {
                    $routeParams['keyword'] = trim($query);
                }

                return $this->redirectToRoute('admin_mbo_catalog_module', $routeParams);
            }
            $context = $contextBuilder->getRecommendedModulesContext($tabClassName);
            $context['recommendation_format'] = $request->get('recommendation_format');
            $response->setData([
                'content' => $this->renderView(
                    '@Modules/ps_mbo/views/templates/admin/controllers/module_catalog/recommended-modules.html.twig',
                    [
                        'shop_context' => $context,
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
