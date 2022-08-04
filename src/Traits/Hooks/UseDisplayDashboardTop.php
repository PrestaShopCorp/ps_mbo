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
        'AdminOrders',
        'AdminInvoices',
        'AdminSlip',
        'AdminDeliverySlip',
        'AdminProducts',
        'AdminFeatures',
        'AdminManufacturers',
        'AdminCartRules',
        'AdminSpecificPriceRule',
        'AdminCustomers',
        'AdminCustomerThreads',
        'AdminStats',
        'AdminCmsContent',
        'AdminImages',
        'AdminShipping',
        'AdminPayment',
        'AdminStatuses',
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
     * The controller of configuration page
     *
     * @var string
     */
    protected static $ADMIN_MODULES_CONTROLLER = 'AdminModules';
    /**
     * Module with push content & link to addons on configuration page
     *
     * @var string[]
     */
    protected static $MODULES_WITH_CONFIGURATION_PUSH = [
        'contactform',
        'blockreassurance',
    ];
    /**
     * The hook is sometimes called multiple times in the same execution because it's called directly in tpl files & in
     * some configurations, multiple files can call/extend those.
     * Try to limit this behavior by adding this tiny boolean attribute and test it on exec.
     *
     * @var bool
     */
    protected $alreadyProcessedPage = false;

    /**
     * @return void
     *
     * @throws \Exception
     */
    public function bootUseDisplayDashboardTop(): void
    {
        if (method_exists($this, 'addAdminControllerMedia')) {
            $this->addAdminControllerMedia('loadMediaForDashboardTop');
        }
    }

    /**
     * Hook displayDashboardTop.
     * Includes content just below the toolbar.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function hookDisplayDashboardTop(): string
    {
        // Check if this page has already been processed by the hook to avoid duplciate content
        if ($this->alreadyProcessedPage) {
            return '';
        }
        $this->alreadyProcessedPage = true;

        $values = Tools::getAllValues();
        //Check if we are on configuration page & if the module needs to have a push on this page
        if (
            isset($values['controller']) &&
            ($values['controller'] === self::$ADMIN_MODULES_CONTROLLER) &&
            isset($values['configure']) &&
            in_array($values['configure'], self::$MODULES_WITH_CONFIGURATION_PUSH)
        ) {
            return $this->displayPushOnConfigurationPage($values['configure']);
        }

        return $this->displayRecommendedModules($values['controller']);
    }

    /**
     * Insert a block on the top of the configuration with a link to addons
     *
     * @param string $moduleName
     *
     * @return string
     */
    protected function displayPushOnConfigurationPage(string $moduleName): string
    {
        switch ($moduleName) {
            case 'contactform':
                $this->smarty->assign([
                    'catchPhrase' => $this->trans('For even more security on your website forms, consult our Security & Access modules category on the'),
                    'linkTarget' => $this->trans('https://addons.prestashop.com/en/429-website-security-access?utm_source=back-office&utm_medium=native-contactform&utm_campaign=back-office-EN&utm_content=security'),
                    'linkText' => $this->trans('PrestaShop Addons Marketplace'),
                ]);
                break;
            case 'blockreassurance':
                $this->smarty->assign([
                    'catchPhrase' => $this->trans('Discover more modules to improve your shop on'),
                    'linkTarget' => $this->trans('https://addons.prestashop.com/en/517-blocks-tabs-banners?utm_source=back-office&utm_medium=modules&utm_campaign=back-office-EN'),
                    'linkText' => $this->trans('PrestaShop Addons Marketplace'),
                ]);
                break;
            default:
                $this->smarty->assign([
                    'catchPhrase' => $this->trans('Discover more modules to improve your shop on'),
                    'linkTarget' => $this->trans('https://addons.prestashop.com/?utm_source=back-office&utm_medium=modules&utm_campaign=back-office-EN'),
                    'linkText' => $this->trans('PrestaShop Addons Marketplace'),
                ]);
        }

        return $this->fetch('module:ps_mbo/views/templates/hook/push-configuration.tpl');
    }

    /**
     * Compute & include data with recommended modules when needed
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function displayRecommendedModules(string $controllerName): string
    {
        if (
            !in_array($controllerName, static::$TABS_WITH_RECOMMENDED_MODULES_BUTTON)
            &&
            !in_array($controllerName, static::$TABS_WITH_RECOMMENDED_MODULES_AFTER_CONTENT)
        ) {
            return '';
        }

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
            'recommendedModulesTitleTranslated' => $this->trans('Recommended Modules and Services', [],
                'Modules.Mbo.Recommendedmodulesandservices'),
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
        $method = 'shouldDisplay' . ucfirst((new UnicodeString($type))->camel()->toString());
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
                    || (
                        $type === static::$RECOMMENDED_AFTER_CONTENT_TYPE
                        && 'AdminCarriers' === Tools::getValue('controller'
                        )
                    );
            }
        }

        return in_array(Tools::getValue('controller'), $modules, true);
    }

    /**
     * Add JS and CSS file
     *
     * @return void
     *
     * @see \PrestaShop\Module\Mbo\Traits\Hooks\UseActionAdminControllerSetMedia
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
