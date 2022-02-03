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

namespace PrestaShop\Module\Mbo\Traits\Hooks;

use Exception;
use PrestaShop\Module\Mbo\Tab\TabCollectionProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\String\UnicodeString;
use ToolsCore as Tools;
use ValidateCore as Validate;

trait UseDisplayDashboardTop
{
    /**
     * @var string
     */
    protected static $RECOMMENDED_BUTTON_TYPE = 'button';
    /**
     * @var string
     */
    protected static $RECOMMENDED_AFTER_CONTENT_TYPE = 'after_content';
    /**
     * @var string[]
     */
    protected static $TABS_WITH_RECOMMENDED_MODULES_BUTTON = [
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
    protected static $TABS_WITH_RECOMMENDED_MODULES_AFTER_CONTENT = [
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
        } catch (Exception $exception) {
            // Avoid fatal errors on ServiceNotFoundException
            return '';
        }

        $this->smarty->assign([
            'shouldAttachRecommendedModulesAfterContent' => $this->shouldAttachRecommendedModules(static::$RECOMMENDED_AFTER_CONTENT_TYPE),
            'shouldAttachRecommendedModulesButton' => $this->shouldAttachRecommendedModules(static::$RECOMMENDED_BUTTON_TYPE),
            'shouldUseLegacyTheme' => $this->isAdminLegacyContext(),
            'recommendedModulesTitleTranslated' => $this->trans('Recommended Modules and Services'),
            'recommendedModulesCloseTranslated' => $this->trans('Close', [], 'Admin.Actions'),
            'recommendedModulesUrl' => $recommendedModulesUrl,
        ]);

        return $this->fetch('module:ps_mbo/views/templates/hook/recommended-modules.tpl');
    }

    /**
     * Indicates if the recommended modules should be attached
     *
     * @param string $type If we want `afterContent` or `button` related modules
     *
     * @return bool
     */
    protected function shouldAttachRecommendedModules(string $type): bool
    {
        $method = 'shouldDisplay' . (new UnicodeString($type))->camel();
        if ($type === static::$RECOMMENDED_BUTTON_TYPE) {
            $modules = static::$TABS_WITH_RECOMMENDED_MODULES_BUTTON;
        } elseif ($type === static::$RECOMMENDED_AFTER_CONTENT_TYPE) {
            $modules = static::$TABS_WITH_RECOMMENDED_MODULES_AFTER_CONTENT;
        } else {
            return false;
        }
        // AdminLogin should not call TabCollectionProvider
        if (Validate::isLoadedObject($this->context->employee)) {
            /** @var TabCollectionProvider $tabCollectionProvider */
            $tabCollectionProvider = $this->get('mbo.tab.collection.provider');
            if ($tabCollectionProvider->isTabCollectionCached()) {
                return $tabCollectionProvider->getTabCollection()->getTab(Tools::getValue('controller'))->{$method}()
                    || 'AdminCarriers' === Tools::getValue('controller');
            }
        }

        return in_array(Tools::getValue('controller'), $modules, true);
    }

    /**
     * Add JS and CSS file
     *
     * @see \PrestaShop\Module\Mbo\Traits\Hooks\UseAdminControllerSetMedia
     *
     * @return void
     */
    protected function loadMediaForDashboardTop(): void
    {
        // has to be loaded in header to prevent flash of content
        $this->context->controller->addJs($this->getPathUri() . 'views/js/recommended-modules.js?v=' . $this->version);

        if ($this->shouldAttachRecommendedModules(static::$RECOMMENDED_BUTTON_TYPE)
            || $this->shouldAttachRecommendedModules(static::$RECOMMENDED_AFTER_CONTENT_TYPE)
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
}
