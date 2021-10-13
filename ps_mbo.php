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
if (!defined('_PS_VERSION_')) {
    exit;
}

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}
use PrestaShop\Module\Mbo\Tab\TabCollectionProvider;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ps_mbo extends Module
{
    const TABS_WITH_RECOMMENDED_MODULES_BUTTON = [
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

    const TABS_WITH_RECOMMENDED_MODULES_AFTER_CONTENT = [
        'AdminMarketing',
        'AdminPayment',
        'AdminCarriers',
    ];

    const ADMIN_CONTROLLERS = [
        'AdminPsMboModule' => [
            'name' => 'Module catalog',
            'visible' => true,
            'class_name' => 'AdminPsMboModule',
            'parent_class_name' => 'AdminParentModulesCatalog',
            'core_reference' => 'AdminModulesCatalog',
        ],
        'AdminPsMboAddons' => [
            'name' => 'Module selection',
            'visible' => true,
            'class_name' => 'AdminPsMboAddons',
            'parent_class_name' => 'AdminParentModulesCatalog',
            'core_reference' => 'AdminAddonsCatalog',
        ],
        'AdminPsMboRecommended' => [
            'name' => 'Module recommended',
            'visible' => true,
            'class_name' => 'AdminPsMboRecommended',
        ],
        'AdminPsMboTheme' => [
            'name' => 'Theme catalog',
            'visible' => true,
            'class_name' => 'AdminPsMboTheme',
            'parent_class_name' => 'AdminParentThemes',
            'core_reference' => 'AdminThemesCatalog',
        ],
    ];

    const HOOKS = [
        'actionAdminControllerSetMedia',
        'displayDashboardTop',
        'displayBackOfficeEmployeeMenu',
        'dashboardZoneTwo',
        'dashboardZoneThree',
    ];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->name = 'ps_mbo';
        $this->version = '2.0.2';
        $this->author = 'PrestaShop';
        $this->tab = 'administration';
        $this->module_key = '6cad5414354fbef755c7df4ef1ab74eb';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.5.0',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->l('PrestaShop Marketplace in your Back Office');
        $this->description = $this->l('Browse the Addons marketplace directly from your back office to better meet your needs.');
    }

    /**
     * Install Module.
     *
     * @return bool
     */
    public function install()
    {
        return parent::install()
            && $this->registerHook(static::HOOKS);
    }

    /**
     * Enable Module.
     *
     * @return bool
     */
    public function enable($force_all = false)
    {
        return parent::enable($force_all)
            && $this->installTabs();
    }

    /**
     * Install all Tabs.
     *
     * @return bool
     */
    public function installTabs()
    {
        foreach (static::ADMIN_CONTROLLERS as $adminTab) {
            if (false === $this->installTab($adminTab)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Install Tab.
     * Used in upgrade script.
     *
     * @param array $tabData
     *
     * @return bool
     */
    public function installTab(array $tabData)
    {
        $position = 0;
        $tabNameByLangId = array_fill_keys(
            Language::getIDs(false),
            $tabData['name']
        );

        if (isset($tabData['core_reference'])) {
            $tabCoreId = Tab::getIdFromClassName($tabData['core_reference']);

            if ($tabCoreId !== false) {
                $tabCore = new Tab($tabCoreId);
                $tabNameByLangId = $tabCore->name;
                $position = $tabCore->position;
                $tabCore->active = false;
                $tabCore->save();
            }
        }

        $tab = new Tab();
        $tab->module = $this->name;
        $tab->class_name = $tabData['class_name'];
        $tab->position = (int) $position;
        $tab->id_parent = empty($tabData['parent_class_name']) ? -1 : Tab::getIdFromClassName($tabData['parent_class_name']);
        $tab->name = $tabNameByLangId;

        if (false === (bool) $tab->add()) {
            return false;
        }

        if (Validate::isLoadedObject($tab)) {
            // Updating the id_parent will override the position, that's why we save 2 times
            $tab->position = (int) $position;
            $tab->save();
        }

        return true;
    }

    /**
     * Disable Module.
     *
     * @return bool
     */
    public function disable($force_all = false)
    {
        return parent::disable($force_all)
            && $this->uninstallTabs();
    }

    /**
     * Uninstall all Tabs.
     *
     * @return bool
     */
    public function uninstallTabs()
    {
        foreach (static::ADMIN_CONTROLLERS as $adminTab) {
            if (false === $this->uninstallTab($adminTab)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Uninstall Tab.
     * Can be used in upgrade script.
     *
     * @param array $tabData
     *
     * @return bool
     */
    public function uninstallTab(array $tabData)
    {
        $tabId = Tab::getIdFromClassName($tabData['class_name']);
        $tab = new Tab($tabId);

        if (false === Validate::isLoadedObject($tab)) {
            return false;
        }

        if (false === (bool) $tab->delete()) {
            return false;
        }

        if (isset($tabData['core_reference'])) {
            $tabCoreId = Tab::getIdFromClassName($tabData['core_reference']);
            $tabCore = new Tab($tabCoreId);

            if (Validate::isLoadedObject($tabCore)) {
                $tabCore->active = true;
            }

            if (false === (bool) $tabCore->save()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Hook actionAdminControllerSetMedia.
     */
    public function hookActionAdminControllerSetMedia()
    {
        // has to be loaded in header to prevent flash of content
        $this->context->controller->addJs($this->getPathUri() . 'views/js/recommended-modules.js?v=' . $this->version);

        if ($this->shouldAttachRecommendedModulesButton()
            || $this->shouldAttachRecommendedModulesAfterContent()
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
     * Hook displayDashboardTop.
     * Includes content just below the toolbar.
     *
     * @return string
     */
    public function hookDisplayDashboardTop()
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
        } catch (Exception $exception) {
            // Avoid fatal errors on ServiceNotFoundException
            return '';
        }

        $this->smarty->assign([
            'shouldAttachRecommendedModulesAfterContent' => $this->shouldAttachRecommendedModulesAfterContent(),
            'shouldAttachRecommendedModulesButton' => $this->shouldAttachRecommendedModulesButton(),
            'shouldUseLegacyTheme' => $this->isAdminLegacyContext(),
            'recommendedModulesTitleTranslated' => $this->trans('Recommended Modules and Services'),
            'recommendedModulesCloseTranslated' => $this->trans('Close', [], 'Admin.Actions'),
            'recommendedModulesUrl' => $recommendedModulesUrl,
        ]);

        return $this->fetch('module:ps_mbo/views/templates/hook/recommended-modules.tpl');
    }

    /**
     * Hook displayBackOfficeMenu.
     * Returns menu in BackOffice
     *
     * @param array $params
     *
     * @return void
     */
    public function hookDisplayBackOfficeEmployeeMenu(array $params)
    {
        if (!class_exists(\PrestaShopBundle\Component\ActionBar\ActionsBarButtonsCollection::class)
            || !class_exists(\PrestaShopBundle\Component\ActionBar\ActionsBarButton::class)
            || !($params['links'] instanceof \PrestaShopBundle\Component\ActionBar\ActionsBarButtonsCollection)) {
            return;
        }
        $params['links']->add(
            new \PrestaShopBundle\Component\ActionBar\ActionsBarButton(
                __CLASS__,
                [
                    'link' => $this->trans(
                        'https://www.prestashop.com/en/resources/documentations?utm_source=back-office&utm_medium=profile&utm_campaign=resources-en&utm_content=download17',
                        [],
                        'Admin.Navigation.Header'
                    ),
                    'icon' => 'book',
                ],
                $this->trans(
                    'Resources',
                    [],
                    'Admin.Navigation.Header'
                )
            )
        );
        $params['links']->add(
            new \PrestaShopBundle\Component\ActionBar\ActionsBarButton(
                __CLASS__,
                [
                    'link' => $this->trans(
                        'https://www.prestashop.com/en/training?utm_source=back-office&utm_medium=profile&utm_campaign=training-en&utm_content=download17',
                        [],
                        'Admin.Navigation.Header'
                    ),
                    'icon' => 'school',
                ],
                $this->trans(
                    'Training',
                    [],
                    'Admin.Navigation.Header'
                )
            )
        );
        $params['links']->add(
            new \PrestaShopBundle\Component\ActionBar\ActionsBarButton(
                __CLASS__,
                [
                    'link' => $this->trans(
                        'https://www.prestashop.com/en/experts?utm_source=back-office&utm_medium=profile&utm_campaign=expert-en&utm_content=download17',
                        [],
                        'Admin.Navigation.Header'
                    ),
                    'icon' => 'person_pin_circle',
                ],
                $this->trans(
                    'Find an Expert',
                    [],
                    'Admin.Navigation.Header'
                )
            )
        );
        $params['links']->add(
            new \PrestaShopBundle\Component\ActionBar\ActionsBarButton(
                __CLASS__,
                [
                    'link' => $this->trans(
                        'https://addons.prestashop.com?utm_source=back-office&utm_medium=profile&utm_campaign=addons-en&utm_content=download17',
                        [],
                        'Admin.Navigation.Header'
                    ),
                    'icon' => 'extension',
                ],
                $this->trans(
                    'PrestaShop Marketplace',
                    [],
                    'Admin.Navigation.Header'
                )
            )
        );
        $params['links']->add(
            new \PrestaShopBundle\Component\ActionBar\ActionsBarButton(
                __CLASS__,
                [
                    'link' => $this->trans(
                        'https://www.prestashop.com/en/contact?utm_source=back-office&utm_medium=profile&utm_campaign=help-center-en&utm_content=download17',
                        [],
                        'Admin.Navigation.Header'
                    ),
                    'icon' => 'help',
                ],
                $this->trans(
                    'Help Center',
                    [],
                    'Admin.Navigation.Header'
                )
            )
        );
    }

    /**
     * Display addons link on the middle column of the dashboard
     *
     * @param  array  $params
     *
     * @return false|string
     */
    public function hookDashboardZoneTwo(array $params)
    {
        return $this->display(__FILE__, 'dashboard-zone-two.tpl');
    }

    /**
     * Display addons data & links in the third column of the dashboard
     *
     * @param  array  $params
     *
     * @return false|string
     */
    public function hookDashboardZoneThree(array $params)
    {
        $moduleManagerBuilder = ModuleManagerBuilder::getInstance();
        $moduleManager = $moduleManagerBuilder->build();

        $this->context->smarty->assign(
            array(
                'new_version_url' => Tools::getCurrentUrlProtocolPrefix() . _PS_API_DOMAIN_ . '/version/check_version.php?v=' . _PS_VERSION_ . '&lang=' . $this->context->language->iso_code . '&autoupgrade=' . (int) ($moduleManager->isInstalled('autoupgrade') && $moduleManager->isEnabled('autoupgrade')) . '&hosted_mode=' . (int) defined('_PS_HOST_MODE_'),
                'help_center_link' => $this->getHelpCenterLink($this->context->language->iso_code),
            )
        );

        return $this->display(__FILE__, 'dashboard-zone-three.tpl');
    }

    /**
     * Indicates if the recommended modules should be attached after content in this page
     *
     * @return bool
     */
    private function shouldAttachRecommendedModulesAfterContent()
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

        return in_array(Tools::getValue('controller'), static::TABS_WITH_RECOMMENDED_MODULES_AFTER_CONTENT, true);
    }

    /**
     * Indicates if the recommended modules button should be attached in this page
     *
     * @return bool
     */
    private function shouldAttachRecommendedModulesButton()
    {
        // AdminLogin should not call TabCollectionProvider
        if (Validate::isLoadedObject($this->context->employee)) {
            /** @var TabCollectionProvider $tabCollectionProvider */
            $tabCollectionProvider = $this->get('mbo.tab.collection.provider');
            if ($tabCollectionProvider->isTabCollectionCached()) {
                return $tabCollectionProvider->getTabCollection()->getTab(Tools::getValue('controller'))->shouldDisplayButton()
                    && 'AdminCarriers' !== Tools::getValue('controller');
            }
        }

        return in_array(Tools::getValue('controller'), static::TABS_WITH_RECOMMENDED_MODULES_BUTTON, true);
    }

    /**
     * Returns the Help center link for the provided locale
     *
     * @param string $languageCode 2-letter locale code
     *
     * @return string
     */
    private function getHelpCenterLink($languageCode)
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

        return isset($links[$languageCode]) ? $links[$languageCode] : $links['en'];
    }

    /**
     * Override of native function to always retrieve Symfony container instead of legacy admin container on legacy context.
     *
     * {@inheritdoc}
     */
    public function get($serviceName)
    {
        if (null === $this->container) {
            $this->container = SymfonyContainer::getInstance();
        }

        return $this->container->get($serviceName);
    }
}
