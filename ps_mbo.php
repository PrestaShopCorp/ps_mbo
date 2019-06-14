<?php
/**
 * 2007-2019 PrestaShop SA and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use PrestaShop\Module\Mbo\Tab\TabCollectionProvider;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ps_mbo extends Module
{
    /**
     * @var array Tabs
     */
    public $adminTabs = [
        'AdminPsMboModule' => [
            'name' => 'Module catalog',
            'visible' => true,
            'class_name' => 'AdminPsMboModule',
            'parent_class_name' => 'AdminParentModulesCatalog',
            'core_reference' => 'AdminModulesCatalog',
        ],
        'AdminPsMboAddons' => [
            'name' => 'Module Selections',
            'visible' => true,
            'class_name' => 'AdminPsMboAddons',
            'parent_class_name' => 'AdminParentModulesCatalog',
            'core_reference' => 'AdminAddonsCatalog',
        ],
        'AdminPsMboTheme' => [
            'name' => 'Theme catalog',
            'visible' => true,
            'class_name' => 'AdminPsMboTheme',
            'parent_class_name' => 'AdminParentThemes',
            'core_reference' => 'AdminThemesCatalog',
        ],
    ];

    /**
     * @var array Hooks used
     */
    public $hooks = [
        'actionAdminControllerSetMedia',
        'displayDashboardTop',
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->name = 'ps_mbo';
        $this->version = '2.0.0';
        $this->author = 'PrestaShop';
        $this->tab = 'administration';
        $this->module_key = '6cad5414354fbef755c7df4ef1ab74eb';
        $this->bootstrap = true;
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.5.0',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->l('PrestaShop Marketplace in your Back Office');
        $this->description = $this->l('Discover the best PrestaShop modules to optimize your online store.');
    }

    /**
     * Install Module.
     *
     * @return bool
     */
    public function install()
    {
        return parent::install()
            && $this->registerHook($this->hooks)
            && $this->installTabs();
    }

    /**
     * Install all Tabs.
     *
     * @return bool
     */
    public function installTabs()
    {
        $result = true;

        foreach ($this->adminTabs as $adminTab) {
            $result &= $this->installTab($adminTab);
        }

        return $result;
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
        $result = true;
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
        $tab->id_parent = Tab::getIdFromClassName($tabData['parent_class_name']);
        $tab->name = $tabNameByLangId;
        $result &= $tab->add();

        if (Validate::isLoadedObject($tab)) {
            // Updating the id_parent will override the position, that's why we save 2 times
            $tab->position = (int) $position;
            $result &= $tab->save();
        }

        return $result;
    }

    /**
     * Uninstall Module.
     *
     * @return bool
     */
    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallTabs();
    }

    /**
     * Uninstall all Tabs.
     *
     * @return bool
     */
    public function uninstallTabs()
    {
        $result = true;

        foreach ($this->adminTabs as $adminTab) {
            $result &= $this->uninstallTab($adminTab);
        }

        return $result;
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
        $result = true;

        $tabId = Tab::getIdFromClassName($tabData['class_name']);
        $tab = new Tab($tabId);

        if (Validate::isLoadedObject($tab)) {
            $result &= $tab->delete();
        }

        if (isset($tabData['core_reference'])) {
            $tabCoreId = Tab::getIdFromClassName($tabData['core_reference']);
            $tabCore = new Tab($tabCoreId);

            if (Validate::isLoadedObject($tabCore)) {
                $tabCore->active = true;
                $result &= $tabCore->save();
            }
        }

        return $result;
    }

    /**
     * Hook actionAdminControllerSetMedia.
     */
    public function hookActionAdminControllerSetMedia()
    {
        // has to be loaded in header to prevent flash of content
        $this->context->controller->addJs($this->getPathUri() . 'views/js/recommended-modules.js?v=' . $this->version);
    }

    /**
     * Hook displayDashboardTop.
     * Includes content just below the toolbar.
     *
     * @return string
     */
    public function hookDisplayDashboardTop()
    {
        $this->smarty->assign([
            'shouldAttachRecommendedModulesAfterContent' => $this->shouldAttachRecommendedModulesAfterContent(),
            'shouldAttachRecommendedModulesButton' => $this->shouldAttachRecommendedModulesButton(),
            'shouldUseLegacyTheme' => $this->isAdminLegacyContext(),
            'recommendedModulesTitleTranslated' => $this->trans('Recommended Modules and Services'), // Retrieved from messages.xlf
            'recommendedModulesCloseTranslated' => $this->trans('Close', [], 'Admin.Actions'),
            'recommendedModulesUrl' => $this->getRouter()->generate(
                'admin_mbo_recommended_modules',
                [
                    'tabClassName' => Tools::getValue('controller'),
                ]
            ),
        ]);

        return $this->fetch('module:ps_mbo/views/templates/hook/recommended-modules.tpl');
    }

    /**
     * Indicates if the recommended modules should be attached after content in this page
     *
     * @return bool
     */
    private function shouldAttachRecommendedModulesAfterContent()
    {
        $tabCollectionProvider = $this->getTabCollectionProvider();
        if ($tabCollectionProvider && $tabCollectionProvider->isTabCollectionCached()) {
            $tab = $tabCollectionProvider->getTab(Tools::getValue('controller'));

            return $tab->shouldDisplayAfterContent()
                || 'AdminCarriers' === Tools::getValue('controller');
        }

        return in_array(
            Tools::getValue('controller'),
            [
                'AdminMarketing',
                'AdminPayment',
                'AdminCarriers',
            ]
        );
    }

    /**
     * Indicates if the recommended modules button should be attached in this page
     *
     * @return bool
     */
    private function shouldAttachRecommendedModulesButton()
    {
        $tabCollectionProvider = $this->getTabCollectionProvider();
        if ($tabCollectionProvider && $tabCollectionProvider->isTabCollectionCached()) {
            $tab = $tabCollectionProvider->getTab(Tools::getValue('controller'));

            return $tab->shouldDisplayButton()
                && 'AdminCarriers' !== Tools::getValue('controller');
        }

        return in_array(
            Tools::getValue('controller'),
            [
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
            ]
        );
    }

    /**
     * @return ContainerInterface
     */
    private function getSymfonyContainer()
    {
        return SymfonyContainer::getInstance();
    }

    /**
     * @return UrlGeneratorInterface
     */
    private function getRouter()
    {
        return $this->getSymfonyContainer()->get('router');
    }

    /**
     * @return TabCollectionProvider|null
     */
    private function getTabCollectionProvider()
    {
        if (!Validate::isLoadedObject($this->context->employee)) {
            // AdminLogin should not call TabCollectionProvider
            return null;
        }

        return $this->getSymfonyContainer()->get('mbo.tab.collection_provider');
    }
}
