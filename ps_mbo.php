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

use Doctrine\Common\Cache\CacheProvider;
use PrestaShop\Module\Mbo\Distribution\AuthenticationProvider;
use PrestaShop\Module\Mbo\Distribution\Client;
use PrestaShop\Module\Mbo\Helpers\Config;
use PrestaShop\Module\Mbo\Tab\TabCollectionProvider;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\PsAccountsInstaller\Installer\Installer;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ps_mbo extends Module
{
    const TABS_WITH_RECOMMENDED_MODULES_BUTTON = [
        'AdminOrders', // Orders> Orders
        'AdminInvoices', // Orders > Invoices
        'AdminSlip', // Orders > Credit Slips
        'AdminDeliverySlip', // Orders > Delivery Slips
        'AdminProducts', // Catalog > Products
        'AdminFeatures', // Catalog > Attributes & Features > Features
        'AdminManufacturers', // Catalog > Brands & Suppliers > Brands
        'AdminCartRules', // Catalog > Discounts > Cart Rules
        'AdminCustomers', // Customers > Customers
        'AdminCustomerThreads', // Customer Service> Customers Service
        'AdminStats', // Stats> Stats
        'AdminCmsContent', // Pages
        'AdminImages', // Image
        'AdminShipping', // Shipping > Preferences
        'AdminStatuses', // Shop Parameters > Order Settings > Statuses
        'AdminGroups', // Shop Parameters > Customer Settings > Groups
        'AdminContacts', // Shop Parameters > Contact > Contact
        'AdminMeta', // Shop Parameters > Traffic & SEO > SEO & URLs
        'AdminSearchConf', // Shop Parameters > Search > Search
        'AdminAdminPreferences', // Advanced Parameters > Administration
        'AdminEmails', // Advanced Parameters > E-mail
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
            'position' => 1,
        ],
        'AdminPsMboAddons' => [
            'name' => 'Module selection',
            'wording' => 'Modules in the spotlight',
            'wording_domain' => 'Modules.Mbo.Modulesselection',
            'visible' => true,
            'class_name' => 'AdminPsMboAddons',
            'parent_class_name' => 'AdminParentModulesCatalog',
            'core_reference' => 'AdminAddonsCatalog',
            'position' => 2,
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
        'AdminPsMboUninstalledModules' => [
            'name' => 'Uninstalled modules',
            'wording' => 'Uninstalled modules',
            'wording_domain' => 'Modules.Mbo.Modulesselection',
            'visible' => true,
            'position' => 2,
            'class_name' => 'AdminPsMboUninstalledModules',
            'parent_class_name' => 'AdminModulesSf',
        ],
    ];

    const CONTROLLERS_WITH_CDC_SCRIPT = [
        'AdminModulesNotifications',
        'AdminModulesUpdates',
        'AdminModulesManage',
        'AdminPsMboModule',
        'AdminModulesCatalog',
    ];

    const HOOKS = [
        'actionAdminControllerSetMedia',
        'actionDispatcherBefore',
        'actionEmployeeSave',
        'actionGeneralPageSave',
        'actionObjectEmployeeUpdateAfter',
        'actionObjectShopUrlUpdateAfter',
        'displayDashboardTop',
    ];

    public $configurationList = [
        'PS_MBO_SHOP_ADMIN_UUID' => '', // 'ADMIN' because there will be only one for all shops in a multishop context
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
     * @var CacheProvider
     */
    public $cacheProvider;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->name = 'ps_mbo';
        $this->version = '3.0.2';
        $this->author = 'PrestaShop';
        $this->tab = 'administration';
        $this->module_key = '6cad5414354fbef755c7df4ef1ab74eb';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.7.0',
            'max' => '1.7.8.99',
        ];

        parent::__construct();

        $this->moduleCacheDir = sprintf('%s/var/modules/%s/', rtrim(_PS_ROOT_DIR_, '/'), $this->name);
        $this->displayName = $this->l('PrestaShop Marketplace in your Back Office');
        $this->description = $this->l('Browse the Addons marketplace directly from your back office to better meet your needs.');

        $this->loadEnv();
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
     * {@inheritDoc}
     */
    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        /**
         * @var AuthenticationProvider $authenticationProvider
         */
        $authenticationProvider = $this->get('mbo.cdc.distribution_authentication_provider');
        $authenticationProvider->clearCache();

        $lockFiles = ['registerShop', 'updateShop'];
        foreach ($lockFiles as $lockFile) {
            if (file_exists($this->moduleCacheDir . $lockFile . '.lock')) {
                unlink($this->moduleCacheDir . $lockFile . '.lock');
            }
        }

        foreach (array_keys($this->configurationList) as $name) {
            Configuration::deleteByName($name);
        }

        // This will reset cached configuration values (uuid, mail, ...) to avoid reusing them
        Config::resetConfigValues();

        return true;
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

        $this->registerShop();

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
        $tabNameByLangId = array_fill_keys(
            Language::getIDs(false),
            $tabData['name']
        );

        if (isset($tabData['core_reference'])) {
            $tabCoreId = Tab::getIdFromClassName($tabData['core_reference']);

            if ($tabCoreId !== false) {
                $tabCore = new Tab($tabCoreId);
                $tabNameByLangId = $tabCore->name;
                $tabCore->active = false;
                $tabCore->save();
            }
        }

        $idParent = empty($tabData['parent_class_name']) ? -1 : Tab::getIdFromClassName($tabData['parent_class_name']);

        $tab = Tab::getInstanceFromClassName($tabData['class_name']);
        if (!Validate::isLoadedObject($tab)) {
            $tab = new Tab();
            $tab->class_name = $tabData['class_name'];
        }

        $tab->module = $this->name;
        $tab->position = Tab::getNewLastPosition($idParent);
        $tab->id_parent = $idParent;
        $tab->name = $tabNameByLangId;
        if (
            true === (bool) version_compare(_PS_VERSION_, '1.7.8', '>=') &&
            !empty($tabData['wording']) &&
            !empty($tabData['wording_domain'])
        ) {
            $tab->wording = $tabData['wording'];
            $tab->wording_domain = $tabData['wording_domain'];
        }

        if (!Validate::isLoadedObject($tab) && false === (bool) $tab->add()) {
            return false;
        }

        if (Validate::isLoadedObject($tab)) {
            $position = 0;
            if (isset($tabData['position'])) {
                $position = $tabData['position'];
            }
            $tab->cleanPositions($tab->id_parent);
            $tab->save();
            $this->putTabInPosition($tab, $position);
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
        $result = parent::disable($force_all)
            && $this->organizeCoreTabs(true)
            && $this->uninstallTabs();

        // Unregister from online services
        $this->unregisterShop();

        return $result;
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
        $this->context->controller->addCSS($this->getPathUri() . 'views/css/catalog.css?v=' . $this->version, 'all', null, false);

        if (Tools::getValue('controller') === 'AdminPsMboModule') {
            $this->context->controller->addJs($this->getPathUri() . 'views/js/upload_module_with_cdc.js?v=' . $this->version);
        }

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

        $this->loadMediaForAdminControllerSetMedia();
    }

    /**
     * Add JS and CSS file
     *
     * @return void
     */
    protected function loadMediaForAdminControllerSetMedia()
    {
        if (in_array(Tools::getValue('controller'), self::CONTROLLERS_WITH_CDC_SCRIPT)) {
            $this->context->controller->addJs('/js/jquery/plugins/growl/jquery.growl.js?v=' . $this->version);
            $this->context->controller->addCSS($this->getPathUri() . 'views/css/module-catalog.css');
        }

        $accountsInstaller = $this->get('mbo.ps_accounts.installer');
        $isPsAccountsEnabled = ($accountsInstaller instanceof Installer) && $accountsInstaller->isModuleEnabled();

        if (
            ('AdminPsMboModule' === Tools::getValue('controller')) // Module catalog / Marketplace
            || $isPsAccountsEnabled // For Module manager
        ) {
            $this->context->controller->addCSS($this->getPathUri() . 'views/css/connection-toolbar.css');
        }
        $this->loadCdcMedia();
    }

    private function loadCdcMedia()
    {
        $controllerName = Tools::getValue('controller');
        if (!is_string($controllerName)) {
            return;
        }
        if (
            !in_array($controllerName, self::CONTROLLERS_WITH_CDC_SCRIPT)
        ) {
            return;
        }

        $cdcJsFile = getenv('MBO_CDC_URL');
        if (false === $cdcJsFile || !is_string($cdcJsFile) || empty($cdcJsFile)) {
            $this->context->controller->addJs($this->getPathUri() . 'views/js/cdc-error.js');

            return;
        }

        $this->context->controller->addJs($cdcJsFile);
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
        $controllerName = Tools::getValue('controller');

        $this->translateTabsIfNeeded();

        // Registration failed on install, retry it
        if (in_array($controllerName, static::CONTROLLERS_WITH_CDC_SCRIPT)) {
            $this->ensureShopIsRegistered();
            $this->ensureShopIsUpdated();
        }
    }

    /**
     * Hook actionGeneralPageSave.
     */
    public function hookActionGeneralPageSave(array $params)
    {
        if (isset($params['route']) && $params['route'] === 'admin_preferences_save') {
            // User may have updated the SSL configuration
            $this->updateShop();
        }
    }

    /**
     * Hook actionEmployeeSave.
     */
    public function hookActionEmployeeSave(array $params)
    {
        $controllerName = Tools::getValue('controller');

        if (
            (isset($params['route']) && $params['route'] === 'admin_employees_edit')
            || ('AdminEmployees' === $controllerName)
        ) {
            // User may have updated the employee language
            $this->postponeTabsTranslations();
        }
    }

    /**
     * Hook actionObjectEmployeeUpdateAfter.
     */
    public function hookActionObjectEmployeeUpdateAfter(array $params)
    {
        $controllerName = Tools::getValue('controller');

        if (
            (isset($params['route']) && $params['route'] === 'admin_employees_edit')
            || ('AdminEmployees' === $controllerName)
        ) {
            // User may have updated the employee language
            $this->postponeTabsTranslations();
        }
    }

    /**
     * Hook actionGeneralPageSave.
     */
    public function hookActionObjectShopUrlUpdateAfter(array $params)
    {
        if ($params['object']->main) {
            // Clear cache to be sure to load correctly the shop with good data whe building the service later
            \Cache::clean('Shop::setUrl_' . (int) $params['object']->id_shop);

            if (Config::isUsingSecureProtocol()) {
                $url = 'https://' . preg_replace('#https?://#', '', $params['object']->domain_ssl);
            } else {
                $url = 'http://' . preg_replace('#https?://#', '', $params['object']->domain);
            }

            $this->updateShop([
                'shop_url' => $url,
            ]);
        }

        return true;
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
            case 'AdminSlip':
            case 'AdminInvoices':
                $title = $this->trans('Simplify accounting', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminDeliverySlip':
                $title = $this->trans('Make shipping easier', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminOrders':
                $title = $this->trans('Boost sales', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminFeatures':
                $title = $this->trans('Optimize product creation', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminSpecificPriceRule':
            case 'AdminCartRules':
                $title = $this->trans('Create a discount strategy', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminManufacturers':
                $title = $this->trans('Promote brands', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminProducts':
                $title = $this->trans('Optimize product catalog', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminStats':
                $title = $this->trans('Improve data strategy', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminContacts':
            case 'AdminCustomerThreads':
            case 'AdminCustomers':
                $title = $this->trans('Improve customer experience', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminCmsContent':
                $title = $this->trans('Customize pages', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminImages':
                $title = $this->trans('Improve visuals', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminCarriers':
                $title = $this->trans('Make your deliveries easier', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminShipping':
                $title = $this->trans('Improve shipping', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminPayment':
                $title = $this->trans('Improve the checkout experience', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminStatuses':
                $title = $this->trans('Optimize order management', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminGroups':
                $title = $this->trans('Improve customer targeting', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminContacts':
                $title = $this->trans('Improve customer experience', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminSearchConf':
            case 'AdminMeta':
                $title = $this->trans('Improve SEO', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminAdminPreferences':
                $title = $this->trans('Simplify store management', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminEmails':
                $title = $this->trans('Automate emails', [], 'Modules.Mbo.Recommendedmodulesandservices');
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
            case 'AdminEmails':
                $description = $this->trans('Send automatic emails and notifications to your customers with ease.<br>
                Here\'s a selection of modules,<strong> compatible with your store</strong>, to help you achieve your goals.', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminAdminPreferences':
                $description = $this->trans('Simplify the daily management of your store and save time.<br>
                Here\'s a selection of modules,<strong> compatible with your store</strong>, to help you achieve your goals.', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminSearchConf':
            case 'AdminMeta':
                $description = $this->trans('Rank higher in search results so more people can find you.<br>
                Here\'s a selection of modules,<strong> compatible with your store</strong>, to help you achieve your goals.', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminContacts':
            case 'AdminCustomers':
            case 'AdminCustomerThreads':
                $description = $this->trans('Create memorable experiences and turn visitors into customers.<br>
                Here\'s a selection of modules,<strong> compatible with your store</strong>, to help you achieve your goals.', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminGroups':
                $description = $this->trans('Manage groups and better target your customers in your marketing.<br>
                Here\'s a selection of modules,<strong> compatible with your store</strong>, to help you achieve your goals.', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminStatuses':
                $description = $this->trans('Save time: delete, edit, and manage your orders in bulk.<br>
                Here\'s a selection of modules,<strong> compatible with your store</strong>, to help you achieve your goals.', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminPayment':
                $description = $this->trans('Offer the payment methods your customers expect and improve your checkout process so you never miss a sale.<br>
                Here\'s a selection of modules,<strong> compatible with your store</strong>, to help you achieve your goals.', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminShipping':
                $description = $this->trans('Optimize your logistics and meet your customers\' delivery expectations.<br>
                Here\'s a selection of modules,<strong> compatible with your store</strong>, to help you achieve your goals.', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminCarriers':
                $description = $this->trans('Make your deliveries easier by choosing the right carriers.<br>
                Here\'s a selection of modules,<strong> compatible with your store</strong>, to help you achieve your goals.', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminImages':
                $description = $this->trans('Use quality and eye-catching visuals while preserving your store’s performance.<br>
                Here\'s a selection of modules,<strong> compatible with your store</strong>, to help you achieve your goals.', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminCmsContent':
                $description = $this->trans('Customize your store pages and highlight special offers.<br>
                Here\'s a selection of modules,<strong> compatible with your store</strong>, to help you achieve your goals.', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminSpecificPriceRule':
            case 'AdminCartRules':
                $description = $this->trans('Drive more sales and increase customer retention with a well-planned discount strategy.<br>
                Here\'s a selection of modules,<strong> compatible with your store</strong>, to help you achieve your goals.', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminManufacturers':
                $description = $this->trans('Promote the brands you distribute and allow your visitors to browse the products of their favorite brands.<br>
                Here\'s a selection of modules,<strong> compatible with your store</strong>, to help you achieve your goals.', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminFeatures':
                $description = $this->trans('Save time on product creation and easily manage combinations.<br>
                Here\'s a selection of modules,<strong> compatible with your store</strong>, to help you achieve your goals.', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminDeliverySlip':
                $description = $this->trans('Save time in preparing and shipping your orders.<br>
                Here\'s a selection of modules,<strong> compatible with your store</strong>, to help you achieve your goals.', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminSlip':
            case 'AdminInvoices':
                $description = $this->trans('Keep your records organized and stay on top of your accounting.<br>
                Here\'s a selection of modules,<strong> compatible with your store</strong>, to help you achieve your goals.', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminOrders':
                $description = $this->trans('Get new customers and keep them coming back.<br>
                Here\'s a selection of modules,<strong> compatible with your store</strong>, to help you achieve your goals.', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminProducts':
                $description = $this->trans('Make your products more visible and create product pages that convert.<br>
                Here\'s a selection of modules, <strong>compatible with your store</strong>, to help you achieve your goals.', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminStats':
                $description = $this->trans('Build a data-driven strategy and make more informed decisions.<br>
                Here\'s a selection of modules,<strong> compatible with your store</strong>, to help you achieve your goals.', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            default:
                $description = $this->trans('Here\'s a selection of modules,<strong> compatible with your store</strong>, to help you achieve your goals', [], 'Modules.Mbo.Recommendedmodulesandservices');
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

        if (null === $this->container) {
            throw new Exception('Cannot get a valid Sf Container');
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

    /**
     * @return void
     */
    private function loadEnv()
    {
        (new Dotenv())->load(__DIR__ . '/.env');
    }

    public function registerShop()
    {
        $this->installConfiguration();
        $this->callServiceWithLockFile('registerShop');
    }

    public function updateShop(array $params = [])
    {
        $this->callServiceWithLockFile('updateShop', $params);
    }

    /**
     * Unregister a shop of online services delivered by API.
     * When the module is disabled or uninstalled, remove it from online services
     *
     * @return void
     */
    private function unregisterShop()
    {
        try {
            $authenticationProvider = $this->get('mbo.cdc.distribution_authentication_provider');
            $distributionApi = $this->get('mbo.cdc.client.distribution_api');

            if (
                $authenticationProvider instanceof AuthenticationProvider
                 && $distributionApi instanceof Client
            ) {
                $distributionApi->setBearer($authenticationProvider->getMboJWT());
                $distributionApi->unregisterShop();
            }
        } catch (Exception $exception) {
            // Do nothing here, the exception is caught to avoid displaying an error to the client
            // Furthermore, the operation can't be tried again later as the module is now disabled or uninstalled
        }
    }

    /**
     * Install configuration for each shop
     *
     * @return bool
     */
    private function installConfiguration()
    {
        $result = true;

        $this->configurationList['PS_MBO_SHOP_ADMIN_UUID'] = Uuid::uuid4()->toString();

        foreach (Shop::getShops(false, null, true) as $shopId) {
            foreach ($this->configurationList as $name => $value) {
                if (false === Configuration::hasKey($name, null, null, (int) $shopId)) {
                    $result = $result && (bool) Configuration::updateValue(
                            $name,
                            $value,
                            false,
                            null,
                            (int) $shopId
                        );
                }
            }
        }

        return $result;
    }

    private function callServiceWithLockFile(string $method, array $params = [])
    {
        $lockFile = $this->moduleCacheDir . $method . '.lock';
        try {
            // If the module is installed via command line or somehow the ADMIN_DIR is not defined,
            // we ignore the shop registration, so it will be done at any action on the backoffice
            if (php_sapi_name() === 'cli' || !defined('_PS_ADMIN_DIR_')) {
                throw new Exception();
            }
            /** @var Client $distributionApi */
            $distributionApi = $this->get('mbo.cdc.client.distribution_api');
            if (!method_exists($distributionApi, $method)) {
                return;
            }

            /**
             * @var AuthenticationProvider $authenticationProvider
             */
            $authenticationProvider = $this->get('mbo.cdc.distribution_authentication_provider');
            $distributionApi->setBearer($authenticationProvider->getMboJWT());
            $distributionApi->{$method}($params);

            if (file_exists($lockFile)) {
                unlink($lockFile);
            }
        } catch (Exception $exception) {
            // Create the lock file
            if (!file_exists($lockFile)) {
                if (!is_dir($this->moduleCacheDir)) {
                    mkdir($this->moduleCacheDir, 0777, true);
                }
                $f = fopen($lockFile, 'w+');
                fclose($f);
            }
        }
    }

    private function ensureShopIsRegistered()
    {
        if (!file_exists($this->moduleCacheDir . 'registerShop.lock')) {
            return;
        }
        $this->registerShop();
    }

    private function ensureShopIsUpdated()
    {
        if (!file_exists($this->moduleCacheDir . 'updateShop.lock')) {
            return;
        }
        $this->updateShop();
    }

    private function putTabInPosition(Tab $tab, int $position)
    {
        // Check tab position in DB
        $dbTabPosition = Db::getInstance()->getValue('
			SELECT `position`
			FROM `' . _DB_PREFIX_ . 'tab`
			WHERE `id_tab` = ' . (int) $tab->id
        );

        if ((int) $dbTabPosition === $position) {
            // Nothing to do, tab is already in the right position
            return;
        }

        Db::getInstance()->execute(
            '
            UPDATE `' . _DB_PREFIX_ . 'tab`
            SET `position` = `position`+1
            WHERE `id_parent` = ' . (int) $tab->id_parent . ' AND `position` >= ' . $position . ' AND `id_tab` <> ' . (int) $tab->id
        );

        Db::getInstance()->execute(
            '
                UPDATE `' . _DB_PREFIX_ . 'tab`
                SET `position` = ' . $position . '
                WHERE `id_tab` = ' . (int) $tab->id
        );
    }
}
