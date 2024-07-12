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

namespace PrestaShop\Module\Mbo\Controller\Admin;

use PrestaShop\Module\Mbo\RecommendedModule\RecommendedModulePresenterInterface;
use PrestaShop\Module\Mbo\Tab\TabCollectionProviderInterface;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tools;

/**
 * Responsible of render json data for ajax display of Recommended Modules.
 */
class ModuleRecommendedController extends FrameworkBundleAdminController
{
    const MBO_AVAILABLE_LANGUAGES = ['en', 'de', 'fr', 'es', 'it', 'nl', 'pl', 'pt', 'ru'];

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var TabCollectionProviderInterface
     */
    private $tabCollectionProvider;

    /**
     * @var RecommendedModulePresenterInterface
     */
    private $recommendedModulePresenter;

    /**
     * @param RequestStack $requestStack
     * @param TabCollectionProviderInterface $tabCollectionProvider
     * @param RecommendedModulePresenterInterface $recommendedModulePresenter
     */
    public function __construct(
        RequestStack $requestStack,
        TabCollectionProviderInterface $tabCollectionProvider,
        RecommendedModulePresenterInterface $recommendedModulePresenter
    ) {
        parent::__construct();
        $this->requestStack = $requestStack;
        $this->tabCollectionProvider = $tabCollectionProvider;
        $this->recommendedModulePresenter = $recommendedModulePresenter;
    }

    /**
     * @return JsonResponse
     */
    public function indexAction()
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
                        'recommendedModulesInstalled' => $this->recommendedModulePresenter->presentCollection($tab->getRecommendedModulesInstalled()),
                        'recommendedModulesNotInstalled' => $this->recommendedModulePresenter->presentCollection($tab->getRecommendedModulesNotInstalled()),
                        'recommendedModulesLinkToAddons' => $this->getRecommendedModulesLinkToAddons(),
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

    /**
     * Customize link to addons of recommended modules modal
     *
     * @return string
     */
    private function getRecommendedModulesLinkToAddons()
    {
        // Get the 3 digits version number. For example, 1.7.6.7 will become 1.7.6
        $psVersion = explode('.', _PS_VERSION_);
        $version = sprintf('%d.%d.%d', (int) $psVersion[0], (int) $psVersion[1], (int) $psVersion[2]);

        // Get the request context language. Fallback to english if not supported
        $locale = $this->getContext()->language->iso_code;
        if (!in_array($locale, self::MBO_AVAILABLE_LANGUAGES)) {
            $locale = 'en';
        }

        $params = [
            'utm_source' => 'back-office',
            'utm_medium' => 'dispatch',
            'utm_campaign' => 'back-office-' . $locale,
            'utm_content' => 'download',
            'compatibility' => $version,
        ];

        $request = $this->requestStack->getCurrentRequest();
        if ($request->request->has('admin_list_from_source')) {
            $params['utm_term'] = $request->request->get('admin_list_from_source');
        }

        $baseUrl = 'https://addons.prestashop.com/' . $locale;

        switch (Tools::getValue('tabClassName')) {
            case 'AdminCustomers':
                $linkToAddons = $baseUrl . '/475-clients';
                break;
            case 'AdminEmails':
                $linkToAddons = $baseUrl . '/437-emails-notifications';
                break;
            case 'AdminAdminPreferences':
                $linkToAddons = $baseUrl . '/440-administration';
                break;
            case 'AdminSearchConf':
                $linkToAddons = $baseUrl . '/510-recherches-filtres';
                break;
            case 'AdminMeta':
                $linkToAddons = $baseUrl . '/488-trafic-marketplaces';
                break;
            case 'AdminContacts':
                $linkToAddons = $baseUrl . '/475-clients';
                break;
            case 'AdminGroups':
                $linkToAddons = $baseUrl . '/537-gestion-clients?';
                break;
            case 'AdminStatuses':
                $linkToAddons = $baseUrl . '/441-gestion-commandes?';
                break;
            case 'AdminPayment':
                $linkToAddons = $baseUrl . '/481-paiement';
                break;
            case 'AdminShipping':
                $linkToAddons = $baseUrl . '/518-livraison-logistique';
                break;
            case 'AdminCarriers':
                $linkToAddons = $baseUrl . '/520-transporteurs';
                break;
            case 'AdminImages':
                $linkToAddons = $baseUrl . '/462-visuels-produits';
                break;
            case 'AdminCmsContent':
                $linkToAddons = $baseUrl . '/516-personnalisation-de-page';
                break;
            case 'AdminStats':
                $linkToAddons = $baseUrl . '/209-tableaux-de-bord';
                break;
            case 'AdminCustomerThreads':
                $linkToAddons = $baseUrl . '/442-service-client';
                break;
            case 'AdminSpecificPriceRule':
            case 'AdminCartRules':
                $linkToAddons = $baseUrl . '/496-promotions-marketing';
                break;
            case 'AdminManufacturers':
                $linkToAddons = $baseUrl . '/512-marques-fabricants';
                break;
            case 'AdminFeatures':
                $linkToAddons = $baseUrl . '/467-declinaisons-personnalisation';
                break;
            case 'AdminProducts':
                $linkToAddons = $baseUrl . '/460-fiche-produit';
                break;
            case 'AdminDeliverySlip':
                $linkToAddons = $baseUrl . '/519-preparation-expedition';
                break;
            case 'AdminSlip':
            case 'AdminInvoices':
                $linkToAddons = $baseUrl . '/446-comptabilite-facturation';
                break;
            case 'AdminOrders':
                $params['benefit_categories[]'] = 3;
                $linkToAddons = $baseUrl . '/2-modules-prestashop';
                break;
            default:
                $linkToAddons = $baseUrl;
                break;
        }

        return $linkToAddons . '?' . http_build_query($params, '', '&');
    }
}
