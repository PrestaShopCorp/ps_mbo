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
        $request = $this->requestStack->getCurrentRequest();
        $psVersion = explode('.', _PS_VERSION_);
        $version = sprintf('%d.%d.%d', (int) $psVersion[0], (int) $psVersion[1], (int) $psVersion[2]);
        $locale = $this->getContext()->language->language_code;
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
        if ($request->request->has('admin_list_from_source')) {
            $params['utm_term'] = $request->request->get('admin_list_from_source');
        }
        switch (Tools::getValue('tabClassName')) {
            case 'AdminEmails':
                $linkToAddons = 'https://addons.prestashop.com/' . $locale . '/437-emails-notifications';
                break;
            case 'AdminAdminPreferences':
                $linkToAddons = 'https://addons.prestashop.com/' . $locale . '/440-administration';
                break;
            case 'AdminSearchConf':
                $linkToAddons = 'https://addons.prestashop.com/' . $locale . '/510-recherches-filtres';
                break;
            case 'AdminMeta':
                $linkToAddons = 'https://addons.prestashop.com/' . $locale . '/488-trafic-marketplaces';
                break;
            case 'AdminContacts':
                $linkToAddons = 'https://addons.prestashop.com/' . $locale . '/475-clients';
                break;
            case 'AdminGroups':
                $linkToAddons = 'https://addons.prestashop.com/' . $locale . '/537-gestion-clients?';
                break;
            case 'AdminStatuses':
                $linkToAddons = 'https://addons.prestashop.com/' . $locale . '/441-gestion-commandes?';
                break;
            case 'AdminPayment':
                $linkToAddons = 'https://addons.prestashop.com/' . $locale . '/481-paiement';
                break;
            case 'AdminShipping':
                $linkToAddons = 'https://addons.prestashop.com/' . $locale . '/518-livraison-logistique';
                break;
            case 'AdminCarriers':
                $linkToAddons = 'https://addons.prestashop.com/' . $locale . '/520-transporteurs';
                break;
            case 'AdminImages':
                $linkToAddons = 'https://addons.prestashop.com/' . $locale . '/462-visuels-produits';
                break;
            case 'AdminCmsContent':
                $linkToAddons = 'https://addons.prestashop.com/' . $locale . '/516-personnalisation-de-page';
                break;
            case 'AdminStats':
                $linkToAddons = 'https://addons.prestashop.com/' . $locale . '/209-tableaux-de-bord';
                break;
            case 'AdminCustomerThreads':
                $linkToAddons = 'https://addons.prestashop.com/' . $locale . '/442-service-client';
                break;
            case 'AdminSpecificPriceRule':
            case 'AdminCartRules':
                $linkToAddons = 'https://addons.prestashop.com/' . $locale . '/496-promotions-marketing';
                break;
            case 'AdminManufacturers':
                $linkToAddons = 'https://addons.prestashop.com/' . $locale . '/512-marques-fabricants';
                break;
            case 'AdminFeatures':
                $linkToAddons = 'https://addons.prestashop.com/' . $locale . '/467-declinaisons-personnalisation';
                break;
            case 'AdminProducts':
                $linkToAddons = 'https://addons.prestashop.com/' . $locale . '/460-fiche-produit';
                break;
            case 'AdminDeliverySlip':
                $linkToAddons = 'https://addons.prestashop.com/' . $locale . '/519-preparation-expedition';
                break;
            case 'AdminSlip':
            case 'AdminInvoices':
                $linkToAddons = 'https://addons.prestashop.com/' . $locale . '/446-comptabilite-facturation';
                break;
            case 'AdminOrders':
                $params['benefit_categories[]'] = 3;
                $linkToAddons = 'https://addons.prestashop.com/' . $locale . '/2-modules-prestashop';
                break;
            default:
                $linkToAddons = 'https://addons.prestashop.com/' . $locale;
                break;
        }
        $linkToAddons = $linkToAddons . '?' . http_build_query($params, '', '&');

        return $linkToAddons;
    }
}
