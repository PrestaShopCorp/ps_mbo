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

namespace PrestaShop\Module\Mbo\Traits;

use PrestaShop\Module\Mbo\Tab\TabCollectionProvider;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;
use PrestaShopBundle\Component\ActionBar\ActionsBarButton;
use PrestaShopBundle\Component\ActionBar\ActionsBarButtonsCollection;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use ToolsCore as Tools;
use ValidateCore as Validate;

trait UseHooks
{
    /**
     * @var string[]
     */
    private static $TABS_WITH_RECOMMENDED_MODULES_BUTTON = [
        'AdminProducts',
        'AdminCategories',
        'AdminTracking',
        'AdminAttributesGroups',
        'AdminFeatures',
        'AdminManufacturers',
        'AdminSuppliers',
        'AdminTags',
        'AdminOrders',
        'AdminInvoices',
        'AdminReturn',
        'AdminDeliverySlip',
        'AdminSlip',
        'AdminStatuses',
        'AdminOrderMessage',
        'AdminCustomers',
        'AdminAddresses',
        'AdminGroups',
        'AdminCarts',
        'AdminCustomerThreads',
        'AdminContacts',
        'AdminCartRules',
        'AdminSpecificPriceRule',
        'AdminShipping',
        'AdminLocalization',
        'AdminZones',
        'AdminCountries',
        'AdminCurrencies',
        'AdminTaxes',
        'AdminTaxRulesGroup',
        'AdminTranslations',
        'AdminPreferences',
        'AdminOrderPreferences',
        'AdminPPreferences',
        'AdminCustomerPreferences',
        'AdminThemes',
        'AdminMeta',
        'AdminCmsContent',
        'AdminImages',
        'AdminSearchConf',
        'AdminGeolocation',
        'AdminInformation',
        'AdminPerformance',
        'AdminEmails',
        'AdminImport',
        'AdminBackup',
        'AdminRequestSql',
        'AdminLogs',
        'AdminAdminPreferences',
        'AdminStats',
        'AdminSearchEngines',
        'AdminReferrers',
    ];
    /**
     * @var string[]
     */
    private static $TABS_WITH_RECOMMENDED_MODULES_AFTER_CONTENT = [
        'AdminMarketing',
        'AdminPayment',
        'AdminCarriers',
    ];

    /**
     * Hook displayDashboardTop.
     * Includes content just below the toolbar.
     *
     * @return string
     */
    public function hookDisplayDashboardTop(): string
    {
        /** @var UrlGeneratorInterface $router */
        $router = $this->get('router');

        try {
            $recommendedModulesUrl = $router->generate(
                'admin_mbo_recommended_modules',
                [
                    'tabClassName' => Tools::getValue('controller'),
                ]
            );
        } catch (\Exception $exception) {
            // Avoid fatal errors on ServiceNotFoundException
            return '';
        }

        $this->smarty->assign([
            'shouldAttachRecommendedModulesAfterContent' => $this->shouldAttachRecommendedModules(static::$TABS_WITH_RECOMMENDED_MODULES_AFTER_CONTENT),
            'shouldAttachRecommendedModulesButton' => $this->shouldAttachRecommendedModules(static::$TABS_WITH_RECOMMENDED_MODULES_BUTTON),
            'shouldUseLegacyTheme' => $this->isAdminLegacyContext(),
            'recommendedModulesTitleTranslated' => $this->trans('Recommended Modules and Services'),
            'recommendedModulesCloseTranslated' => $this->trans('Close', [], 'Admin.Actions'),
            'recommendedModulesUrl' => $recommendedModulesUrl,
        ]);

        return $this->fetch('module:ps_mbo/views/templates/hook/recommended-modules.tpl');
    }

    /**
     * Display addons link on the middle column of the dashboard
     *
     * @param array $params
     *
     * @return false|string
     */
    public function hookDashboardZoneTwo(array $params)
    {
        return $this->display($this->name, 'dashboard-zone-two.tpl');
    }

    /**
     * Display addons data & links in the third column of the dashboard
     *
     * @param array $params
     *
     * @return false|string
     */
    public function hookDashboardZoneThree(array $params)
    {
        $moduleManagerBuilder = ModuleManagerBuilder::getInstance();
        $moduleManager = $moduleManagerBuilder->build();

        $this->context->smarty->assign(
            [
                'new_version_url' => Tools::getCurrentUrlProtocolPrefix() . _PS_API_DOMAIN_ . '/version/check_version.php?v=' . _PS_VERSION_ . '&lang=' . $this->context->language->iso_code . '&autoupgrade=' . (int) ($moduleManager->isInstalled('autoupgrade') && $moduleManager->isEnabled('autoupgrade')) . '&hosted_mode=' . (int) defined('_PS_HOST_MODE_'),
                'help_center_link' => $this->getHelpCenterLink($this->context->language->iso_code),
            ]
        );

        return $this->display($this->name, 'dashboard-zone-three.tpl');
    }

    /**
     * Hook displayBackOfficeMenu.
     * Returns menu in BackOffice
     *
     * @param array $params
     *
     * @return void
     */
    public function hookDisplayBackOfficeEmployeeMenu(array $params): void
    {
        if (!class_exists(ActionsBarButtonsCollection::class)
            || !class_exists(ActionsBarButton::class)
            || !($params['links'] instanceof ActionsBarButtonsCollection)) {
            return;
        }

        $links = [
            [
                'url' => 'https://www.prestashop.com/en/resources/documentations?utm_source=back-office&utm_medium=profile&utm_campaign=resources-en&utm_content=download17',
                'icon' => 'book',
                'label' => 'Resources',
            ],
            [
                'url' => 'https://www.prestashop.com/en/training?utm_source=back-office&utm_medium=profile&utm_campaign=training-en&utm_content=download17',
                'icon' => 'school',
                'label' => 'Training',
            ],
            [
                'url' => 'https://www.prestashop.com/en/experts?utm_source=back-office&utm_medium=profile&utm_campaign=expert-en&utm_content=download17',
                'icon' => 'person_pin_circle',
                'label' => 'Find an Expert',
            ],
            [
                'url' => 'https://addons.prestashop.com?utm_source=back-office&utm_medium=profile&utm_campaign=addons-en&utm_content=download17',
                'icon' => 'extension',
                'label' => 'PrestaShop Marketplace',
            ],
            [
                'url' => 'https://www.prestashop.com/en/contact?utm_source=back-office&utm_medium=profile&utm_campaign=help-center-en&utm_content=download17',
                'icon' => 'help',
                'label' => 'Help Center',
            ],
        ];

        foreach ($links as $link) {
            $params['links']->add(
                new ActionsBarButton(
                    __CLASS__,
                    [
                        'link' => $this->trans($link['url'], [], 'Admin.Navigation.Header'),
                        'icon' => $link['icon'],
                    ],
                    $this->trans($link['label'], [], 'Admin.Navigation.Header')
                )
            );
        }
    }

    /**
     * Hook actionAdminControllerSetMedia.
     */
    public function hookActionAdminControllerSetMedia(): void
    {
        // has to be loaded in header to prevent flash of content
        $this->context->controller->addJs($this->getPathUri() . 'views/js/recommended-modules.js?v=' . $this->version);

        if ($this->shouldAttachRecommendedModules(static::$TABS_WITH_RECOMMENDED_MODULES_BUTTON)
            || $this->shouldAttachRecommendedModules(static::$TABS_WITH_RECOMMENDED_MODULES_AFTER_CONTENT)
        ) {
            $this->context->controller->addCSS($this->getPathUri() . 'views/css/recommended-modules.css');
            $this->context->controller->addJs(
                rtrim(__PS_BASE_URI__, '/')
                . str_ireplace(
                    _PS_CORE_DIR_,
                    '',
                    _PS_BO_ALL_THEMES_DIR_
                )
                . 'default/js/bundle/module/module_card.js?v='
                . _PS_VERSION_
            );
        }
    }

    /**
     * Indicates if the recommended modules should be attached
     *
     * @param array $modules
     *
     * @return bool
     */
    private function shouldAttachRecommendedModules(array $modules): bool
    {
        // AdminLogin should not call TabCollectionProvider
        if (Validate::isLoadedObject($this->context->employee)) {
            /** @var TabCollectionProvider $tabCollectionProvider */
            $tabCollectionProvider = $this->get('mbo.tab.collection.provider');
            if ($tabCollectionProvider->isTabCollectionCached()) {
                return $tabCollectionProvider->getTabCollection()->getTab(Tools::getValue('controller'))->shouldDisplayAfterContent()
                    || 'AdminCarriers' === Tools::getValue('controller');
            }
        }

        return in_array(Tools::getValue('controller'), $modules, true);
    }

    /**
     * Returns the Help center link for the provided locale
     *
     * @param string $languageCode 2-letter locale code
     *
     * @return string
     */
    private function getHelpCenterLink(string $languageCode): string
    {
        $links = [
            'fr' => 'https://www.prestashop.com/fr/contact?utm_source=back-office&utm_medium=links&utm_campaign=help-center-fr&utm_content=download17',
            'en' => 'https://www.prestashop.com/en/contact?utm_source=back-office&utm_medium=links&utm_campaign=help-center-en&utm_content=download17',
            'es' => 'https://www.prestashop.com/es/contacto?utm_source=back-office&utm_medium=links&utm_campaign=help-center-es&utm_content=download17',
            'de' => 'https://www.prestashop.com/de/kontakt?utm_source=back-office&utm_medium=links&utm_campaign=help-center-de&utm_content=download17',
            'it' => 'https://www.prestashop.com/it/contatti?utm_source=back-office&utm_medium=links&utm_campaign=help-center-it&utm_content=download17',
            'nl' => 'https://www.prestashop.com/nl/contacteer-ons?utm_source=back-office&utm_medium=links&utm_campaign=help-center-nl&utm_content=download17',
            'pt' => 'https://www.prestashop.com/pt/contato?utm_source=back-office&utm_medium=links&utm_campaign=help-center-pt&utm_content=download17',
            'pl' => 'https://www.prestashop.com/pl/kontakt?utm_source=back-office&utm_medium=links&utm_campaign=help-center-pl&utm_content=download17',
        ];

        return $links[$languageCode] ?? $links['en'];
    }
}
