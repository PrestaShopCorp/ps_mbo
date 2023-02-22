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

    const CORE_TABS_RENAMED = [
        'AdminModulesCatalog' => [
            'old_name' => 'Modules catalog',
            'new_name' => 'Marketplace',
        ],
        'AdminParentModulesCatalog' => [
            'old_name' => 'Modules catalog',
            'new_name' => 'Marketplace',
        ],
        'AdminAddonsCatalog' => [
            'old_name' => 'Module selection',
            'new_name' => 'Modules in the spotlight',
            'trans_domain' => 'Modules.Mbo.Modulesselection',
        ],
    ];

    const ADMIN_CONTROLLERS = [
        'AdminPsMboModule' => [
            'name' => 'Marketplace',
            'visible' => true,
            'class_name' => 'AdminPsMboModule',
            'parent_class_name' => 'AdminParentModulesCatalog',
            'core_reference' => 'AdminModulesCatalog',
        ],
        'AdminPsMboAddons' => [
            'name' => 'Module selection',
            'wording' => 'Modules in the spotlight',
            'wording_domain' => 'Modules.Mbo.Modulesselection',
            'visible' => true,
            'class_name' => 'AdminPsMboAddons',
            'parent_class_name' => 'AdminParentModulesCatalog',
            'core_reference' => 'AdminAddonsCatalog',
        ],
        'AdminPsMboRecommended' => [
            'name' => 'Module recommended',
            'wording' => 'Recommended Modules and Services',
            'wording_domain' => 'Modules.Mbo.Recommendedmodulesandservices',
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
        'actionDispatcherBefore',
        'displayDashboardTop',
    ];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var string
     */
    public $moduleCacheDir;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->name = 'ps_mbo';
        $this->version = '2.1.0';
        $this->author = 'PrestaShop';
        $this->tab = 'administration';
        $this->module_key = '6cad5414354fbef755c7df4ef1ab74eb';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.5.0',
            'max' => '1.7.8.8',
        ];

        parent::__construct();

        $this->moduleCacheDir = sprintf('%s/var/modules/%s/', rtrim(_PS_ROOT_DIR_, '/'), $this->name);
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
        $result = parent::enable($force_all)
            && $this->organizeCoreTabs()
            && $this->installTabs();

        $this->postponeTabsTranslations();

        return (bool) $result;
    }

    /**
     * This method is here if we need to rename some Core tabs.
     *
     * @return bool
     */
    public function organizeCoreTabs($restore = false)
    {
        $return = true;
        $coreTabsRenamed = static::CORE_TABS_RENAMED;

        if (true === (bool) version_compare(_PS_VERSION_, '1.7.8', '<')) {
            unset($coreTabsRenamed['AdminAddonsCatalog']);
        }

        // Rename tabs
        foreach ($coreTabsRenamed as $className => $names) {
            $tabId = Tab::getIdFromClassName($className);

            if ($tabId !== false) {
                $tabNameByLangId = [];
                if ($restore) {
                    $name = $names['old_name'];
                    $transDomain = 'Admin.Navigation.Menu';
                } else {
                    $name = $names['new_name'];
                    $transDomain = isset($names['trans_domain']) ? $names['trans_domain'] : 'Modules.Mbo.Global';
                }
                foreach (Language::getIDs(false) as $langId) {
                    $langId = (int) $langId;
                    $language = new Language($langId);
                    $tabNameByLangId[$langId] = (string) $this->trans(
                        $name,
                        [],
                        $transDomain,
                        !empty($language->locale) ? $language->locale : $language->language_code // can't use getLocale because not existing in 1.7.5
                    );
                }

                $tab = new Tab($tabId);
                $tab->name = $tabNameByLangId;
                if (true === (bool) version_compare(_PS_VERSION_, '1.7.8', '>=')) {
                    $tab->wording = $name;
                    $tab->wording_domain = $transDomain;
                }
                $return &= $tab->save();
            }
        }

        // Change tabs positions
        $return &= $this->changeTabPosition('AdminParentModulesCatalog', $restore ? 1 : 0);
        $return &= $this->changeTabPosition('AdminModulesSf', $restore ? 0 : 1);

        return (bool) $return;
    }

    public function changeTabPosition($className, $newPosition)
    {
        $return = true;
        $tabId = Tab::getIdFromClassName($className);

        if ($tabId !== false) {
            $tab = new Tab($tabId);
            $tab->position = $newPosition;
            $return &= $tab->save();
        }

        return $return;
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
        if (
            true === (bool) version_compare(_PS_VERSION_, '1.7.8', '>=') &&
            !empty($tabData['wording']) &&
            !empty($tabData['wording_domain'])
        ) {
            $tab->wording = $tabData['wording'];
            $tab->wording_domain = $tabData['wording_domain'];
        }

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
            && $this->organizeCoreTabs(true)
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
        $this->context->controller->addCSS($this->getPathUri() . 'views/css/catalog.css');

        if (
            $this->shouldAttachRecommendedModulesButton()
            || $this->shouldAttachRecommendedModulesAfterContent()
        ) {
            if (
                true === (bool) version_compare(_PS_VERSION_, '1.7.8', '>=')
            ) {
                $this->context->controller->addCSS($this->getPathUri() . 'views/css/recommended-modules-since-1.7.8.css');
            }
            if (
                true === (bool) version_compare(_PS_VERSION_, '1.7.8', '<')
            ) {
                $this->context->controller->addCSS($this->getPathUri() . 'views/css/recommended-modules-lower-1.7.8.css');
            }
            if (Tools::getValue('controller') !== 'AdminProducts') {
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
            'recommendedModulesTitleTranslated' => $this->getRecommendedModulesButtonTitle(),
            'recommendedModulesDescriptionTranslated' => $this->getRecommendedModulesDescription(),
            'recommendedModulesCloseTranslated' => $this->trans('Close', [], 'Admin.Actions'),
            'recommendedModulesUrl' => $recommendedModulesUrl,
        ]);

        return $this->fetch('module:ps_mbo/views/templates/hook/recommended-modules.tpl');
    }

    /**
     * Hook actionDispatcherBefore.
     */
    public function hookActionDispatcherBefore()
    {
        $this->translateTabsIfNeeded();
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
     * Customize title button recommended modules
     *
     * @return string
     */
    private function getRecommendedModulesButtonTitle()
    {
        switch (Tools::getValue('controller')) {
            case 'AdminInvoices':
            case 'AdminDeliverySlip':
            case 'AdminSlip':
            case 'AdminOrders':
                $title = $this->trans('Boost sales', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminSpecificPriceRule':
            case 'AdminManufacturers':
            case 'AdminFeatures':
            case 'AdminCartRules':
            case 'AdminProducts':
                $title = $this->trans('Optimize product catalog', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminStats':
                $title = $this->trans('Improve data strategy', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminCustomerThreads':
            case 'AdminCustomers':
                $title = $this->trans('Improve customer experience', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            default:
                $title = $this->trans('Recommended modules', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
        }

        return $title;
    }

    /**
     * Customize description of modal button recommended modules
     *
     * @return string
     */
    private function getRecommendedModulesDescription()
    {
        switch (Tools::getValue('controller')) {
            case 'AdminInvoices':
            case 'AdminDeliverySlip':
            case 'AdminSlip':
            case 'AdminOrders':
                $description = $this->trans('Get new customers and keep them coming back.<br>
                Here\'s a selection of partner modules,<strong> compatible with your store</strong>, to help you achieve your goals.', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminSpecificPriceRule':
            case 'AdminManufacturers':
            case 'AdminFeatures':
            case 'AdminCartRules':
            case 'AdminProducts':
                $description = $this->trans('Make your more products visible and create product pages that convert.<br>
                Here\'s a selection of partner modules, <strong>compatible with your store</strong>, to help you achieve your goals.', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminStats':
                $description = $this->trans('<p>Build a data-driven strategy and take more informed decisions.<br>
                Here\'s a selection of partner modules,<strong> compatible with your store</strong>, to help you achieve your goals.</p>', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminCustomerThreads':
            case 'AdminCustomers':
                $description = $this->trans('Create memorable experiences and turn visitors into customers.<br>
                Here\'s a selection of partner modules,<strong> compatible with your store</strong>, to help you achieve your goals.', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            default:
                $description = $this->trans('Here\'s a selection of partner modules,<strong> compatible with your store</strong>, to help you achieve your goals', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
        }

        return $description;
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

    public function isUsingNewTranslationSystem()
    {
        return false;
    }

    /**
     * Update hooks in DB.
     * Search current hooks registered in DB and compare them with the hooks declared in the module.
     * If a hook is missing, it will be added. If a hook is not declared in the module, it will be removed.
     *
     * @return void
     */
    public function updateHooks()
    {
        $hookData = (array) Db::getInstance()->executeS(
            '
            SELECT DISTINCT(phm.id_hook), name
            FROM `' . _DB_PREFIX_ . 'hook_module` phm
            JOIN `' . _DB_PREFIX_ . 'hook` ph ON ph.id_hook=phm.id_hook
            WHERE `id_module` = ' . (int) $this->id
        );

        $oldHooks = [];
        $newHooks = [];

        // Iterate on DB hooks to get only the old ones
        foreach ($hookData as $hook) {
            if (!in_array(strtolower($hook['name']), array_map('strtolower', static::HOOKS))) {
                $oldHooks[] = $hook;
            }
        }

        // Iterate on module hooks to get only the new ones
        foreach (static::HOOKS as $moduleHook) {
            $isNew = true;
            foreach ($hookData as $hookInDb) {
                if (strtolower($moduleHook) === strtolower($hookInDb['name'])) {
                    $isNew = false;
                    break;
                }
            }
            if ($isNew) {
                $newHooks[] = $moduleHook;
            }
        }

        foreach ($oldHooks as $oldHook) {
            $this->unregisterHook($oldHook['id']);
        }
        // we iterate because registerHook accepts array only since 1.7.7.0
        foreach ($newHooks as $newHook) {
            $this->registerHook($newHook);
        }
    }

    public function postponeTabsTranslations()
    {
        /**it'
         * There is an issue for translating tabs during installation :
         * Active modules translations files are loaded during the kernel boot. So the installing module translations are not known
         * So, we postpone the tabs translations for the first time the module's code is executed.
         */
        $lockFile = $this->moduleCacheDir . 'translate_tabs.lock';
        if (!file_exists($lockFile)) {
            if (!is_dir($this->moduleCacheDir)) {
                mkdir($this->moduleCacheDir, 0777, true);
            }
            $f = fopen($lockFile, 'w+');
            fclose($f);
        }
    }

    private function translateTabsIfNeeded()
    {
        try {
            if (Tools::getValue('controller') === 'AdminCommon') {
                return; // Avoid early translation by notifications controller
            }
            $lockFile = $this->moduleCacheDir . 'translate_tabs.lock';
            if (!file_exists($lockFile)) {
                return;
            }

            $languages = Language::getLanguages(false);

            // Because the wording and wording_domain are introduced since PS v1.7.8.0 and we cannot use them
            if (true === (bool) version_compare(_PS_VERSION_, '1.7.8', '>=')) {
                $this->translateTabs();
            }

            @unlink($lockFile);
        } catch (\Exception $e) {
            // Do nothing
        }
    }

    private function translateTabs()
    {
        $moduleTabs = Tab::getCollectionFromModule($this->name);
        $languages = Language::getLanguages(false);

        /**
         * @var Tab $tab
         */
        foreach ($moduleTabs as $tab) {
            $this->translateTab($tab, $languages);
        }

        foreach (static::CORE_TABS_RENAMED as $coreTabClass => $coreTabRenamed) {
            if (array_key_exists('trans_domain', $coreTabRenamed)) {
                $tab = Tab::getInstanceFromClassName($coreTabClass);
                $this->translateTab($tab, $languages);
            }
        }
    }

    private function translateTab($tab, $languages)
    {
        if (!$tab instanceof Tab) {
            throw new \Exception('First argument of translateTab mut be a Tab instance');
        }

        if (!empty($tab->wording) && !empty($tab->wording_domain)) {
            $tabNameByLangId = [];
            foreach ($languages as $language) {
                $tabNameByLangId[$language['id_lang']] = $this->trans(
                    $tab->wording,
                    [],
                    $tab->wording_domain,
                    $language['locale']
                );
            }

            $tab->name = $tabNameByLangId;
            $tab->save();
        }
    }
}
