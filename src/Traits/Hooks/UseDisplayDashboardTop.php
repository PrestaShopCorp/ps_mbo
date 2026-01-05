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

use PrestaShop\Module\Mbo\Exception\ExpectedServiceNotFoundException;
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;
use PrestaShop\Module\Mbo\Service\View\ContextBuilder;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

if (!defined('_PS_VERSION_')) {
    exit;
}

trait UseDisplayDashboardTop
{
    /**
     * @var string
     */
    public static string $recommendedButtonType = 'button';

    /**
     * @var string
     */
    public static string $recommendedAfterContentType = 'after_content';

    /**
     * @var string[]
     */
    public static array $tabsWithRecommendedButton = [
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
        'AdminStatuses', // Shop Parameters > Order Settings > Statuses
        'AdminGroups', // Shop Parameters > Customer Settings > Groups
        'AdminContacts', // Shop Parameters > Contact > Contact
        'AdminMeta', // Shop Parameters > Traffic & SEO > SEO & URLs
        'AdminSearchConf', // Shop Parameters > Search > Search
        'AdminAdminPreferences', // Advanced Parameters > Administration
        'AdminEmails', // Advanced Parameters > E-mail
    ];

    /**
     * @var string[]
     */
    public static array $tabsWithRecommendedAfterContent = [
        'AdminPayment',
        'AdminCarriers',
    ];

    /**
     * Module with push content & link to addons on configuration page
     *
     * @var string[]
     */
    protected static array $modulesWithConfigurationPush = [
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
    protected bool $alreadyProcessedPage = false;

    protected array $controllersWithRecommendedModules = [];

    protected static array $routeAfterContent = [
        'admin_carriers_index',
        'admin_payment_methods',
        'admin_legacy_controller_route',
    ];

    /**
     * Hook displayDashboardTop.
     * Includes content just below the toolbar.
     *
     * @param $params
     * @return string
     *
     * @throws \Exception
     */
    public function hookDisplayDashboardTop($params): string
    {
        // Check if this page has already been processed by the hook to avoid duplicate content
        if ($this->alreadyProcessedPage) {
            return '';
        }
        $this->controllersWithRecommendedModules = [
            self::$recommendedButtonType => self::$tabsWithRecommendedButton,
            self::$recommendedAfterContentType => self::$tabsWithRecommendedAfterContent,
        ];
        $this->alreadyProcessedPage = true;

        if ($this->shouldDisplayModuleManagerMessage($params)) {
            return $this->renderModuleManagerMessage();
        }

        // Check if we are on a configuration page and if the module needs to have a push on this page
        $shouldDisplayMessageInConfigPage = isset($params['route'])
            && $params['route'] === 'admin_module_configure_action'
            && isset($params['request'])
            && in_array($params['request']->get('module_name'), self::$modulesWithConfigurationPush);

        return $shouldDisplayMessageInConfigPage
            ? $this->displayPushOnConfigurationPage($params['request']->get('module_name'))
            : $this->displayRecommendedModules($this->context->controller->controller_name ?? '', $params);
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
        $defaultLinkText = $this->trans('PrestaShop Addons Marketplace', [], 'Modules.Mbo.Global');
        switch ($moduleName) {
            case 'contactform':
                $this->smarty->assign([
                    'catchPhrase' => $this->trans(
                        'For even more security on your website forms, consult our Security & Access modules category on the',
                        [],
                        'Modules.Mbo.Global'
                    ),
                    'linkTarget' => $this->trans(
                        'https://addons.prestashop.com/en/429-website-security-access?utm_source=back-office&utm_medium=native-contactform&utm_campaign=back-office-EN&utm_content=security',
                        [],
                        'Modules.Mbo.Links'
                    ),
                    'linkText' => $defaultLinkText,
                ]);
                break;
            case 'blockreassurance':
                $this->smarty->assign([
                    'catchPhrase' => $this->trans(
                        'Discover more modules to improve your shop on',
                        [],
                        'Modules.Mbo.Global'
                    ),
                    'linkTarget' => $this->trans(
                        'https://addons.prestashop.com/en/517-blocks-tabs-banners?utm_source=back-office&utm_medium=modules&utm_campaign=back-office-EN',
                        [],
                        'Modules.Mbo.Links'
                    ),
                    'linkText' => $defaultLinkText,
                ]);
                break;
            default:
                $this->smarty->assign([
                    'catchPhrase' => $this->trans(
                        'Discover more modules to improve your shop on',
                        [],
                        'Modules.Mbo.Global'
                    ),
                    'linkTarget' => $this->trans(
                        'https://addons.prestashop.com/?utm_source=back-office&utm_medium=modules&utm_campaign=back-office-EN',
                        [],
                        'Modules.Mbo.Links'
                    ),
                    'linkText' => $defaultLinkText,
                ]);
        }

        return $this->fetch('module:ps_mbo/views/templates/hook/push-configuration.tpl');
    }

    /**
     * Compute & include data with recommended modules when needed
     *
     * @throws \Exception
     */
    protected function displayRecommendedModules(string $controller, array $hookParams): string
    {
        $shouldAttachRecommendedModulesAfterContent = $this->shouldAttachRecommendedModules(self::$recommendedAfterContentType);
        $shouldAttachRecommendedModulesButton = $this->shouldAttachRecommendedModules(self::$recommendedButtonType);

        // Show only in content if index page
        $shouldDisplayAfterContent = isset($hookParams['route']) && in_array($hookParams['route'], self::$routeAfterContent);

        if ((!$shouldAttachRecommendedModulesAfterContent && !$shouldAttachRecommendedModulesButton)
            || ($shouldAttachRecommendedModulesAfterContent && !$shouldDisplayAfterContent)) {
            return '';
        }

        try {
            /** @var UrlGeneratorInterface|null $router */
            $router = $this->get('prestashop.router');
            if (null === $router) {
                throw new ExpectedServiceNotFoundException('Some services not found in UseDisplayDashboardTop');
            }

            $recommendedModulesUrl = $router->generate(
                'admin_mbo_recommended_modules',
                [
                    'tabClassName' => $this->context->controller->controller_name,
                    'recommendation_format' => $shouldAttachRecommendedModulesButton ? 'modal' : 'card',
                ]
            );
        } catch (\Exception $exception) {
            // Avoid fatal errors on ServiceNotFoundException
            ErrorHelper::reportError($exception);

            return '';
        }

        $this->context->controller->addJs($this->getPathUri() . 'views/js/recommended-modules.js?v=' . $this->version);
        $this->context->controller->addCSS($this->getPathUri() . 'views/css/recommended-modules.css');

        $extraParams = self::getCdcMediaUrl();
        $moduleUri = __PS_BASE_URI__ . 'modules/ps_mbo/';

        $extraParams['recommended_modules_js'] = $moduleUri . 'views/js/recommended-modules.js?v=' . self::VERSION;
        $extraParams['recommended_modules_css'] = $moduleUri . 'views/css/recommended-modules.css?v=' . self::VERSION;

        $this->smarty->assign([
            'shouldAttachRecommendedModulesAfterContent' => $shouldAttachRecommendedModulesAfterContent,
            'shouldAttachRecommendedModulesButton' => $shouldAttachRecommendedModulesButton,
            'shouldUseLegacyTheme' => $this->isAdminLegacyContext() || $hookParams['route'] === 'admin_legacy_controller_route',
            'recommendedModulesCloseTranslated' => $this->trans('Close', [], 'Admin.Actions'),
            'recommendedModulesUrl' => $recommendedModulesUrl,
            'recommendedModulesTitleTranslated' => $this->getRecommendedModulesButtonTitle($controller),
        ] + $extraParams);

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
        if ($type === self::$recommendedButtonType) {
            $modules = $this->controllersWithRecommendedModules[self::$recommendedButtonType];
        } elseif ($type === self::$recommendedAfterContentType) {
            $modules = $this->controllersWithRecommendedModules[self::$recommendedAfterContentType];
        } else {
            return false;
        }

        return in_array($this->context->controller->controller_name, $modules, true);
    }

    /**
     * Customize title button recommended modules
     */
    private function getRecommendedModulesButtonTitle(string $controller): string
    {
        switch ($controller) {
            case 'AdminEmails':
                $title = $this->trans('Automate emails', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminOrders':
                $title = $this->trans('Boost sales', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminCartRules':
            case 'AdminSpecificPriceRule':
                $title = $this->trans('Create a discount strategy', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminCmsContent':
                $title = $this->trans('Customize pages', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminContacts':
            case 'AdminCustomers':
            case 'AdminCustomerThreads':
                $title = $this->trans('Improve customer experience', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminGroups':
                $title = $this->trans('Improve customer targeting', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminStats':
                $title = $this->trans('Improve data strategy', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminMeta':
            case 'AdminSearchConf':
                $title = $this->trans('Improve SEO', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminShipping':
                $title = $this->trans('Improve shipping', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminPayment':
                $title = $this->trans(
                    'Improve the checkout experience',
                    [],
                    'Modules.Mbo.Recommendedmodulesandservices'
                );
                break;
            case 'AdminImages':
                $title = $this->trans('Improve visuals', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminDeliverySlip':
                $title = $this->trans('Make shipping easier', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminCarriers':
                $title = $this->trans('Make your deliveries easier', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminStatuses':
                $title = $this->trans('Optimize order management', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminProducts':
                $title = $this->trans('Optimize product catalog', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminFeatures':
                $title = $this->trans('Optimize product creation', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminManufacturers':
                $title = $this->trans('Promote brands', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminInvoices':
            case 'AdminSlip':
                $title = $this->trans('Simplify accounting', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            case 'AdminAdminPreferences':
                $title = $this->trans('Simplify store management', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
            default:
                $title = $this->trans('Recommended modules', [], 'Modules.Mbo.Recommendedmodulesandservices');
                break;
        }

        return $title;
    }

    private function renderModuleManagerMessage(): string
    {
        try {
            /** @var Environment|null $twig */
            $twig = $this->get('twig');
            /** @var ContextBuilder|null $contextBuilder */
            $contextBuilder = $this->get(ContextBuilder::class);

            if (null === $contextBuilder || null === $twig) {
                throw new ExpectedServiceNotFoundException('Some services not found in UseDisplayAdminAfterHeader');
            }

            $extraParams = self::getCdcMediaUrl();

            return $twig->render(
                '@Modules/ps_mbo/views/templates/hook/twig/module_manager_message.html.twig', [
                    'shop_context' => $contextBuilder->getViewContext(),
                ] + $extraParams,
            );
        } catch (\Exception $e) {
            ErrorHelper::reportError($e);

            return '';
        }
    }

    private function shouldDisplayModuleManagerMessage($params = []): bool
    {  
        if (!isset($params['route'])) {
            return false;
        }
        
        return in_array(
            $params['route'],
            [
                'admin_module_manage',
                'admin_module_notification',
                'admin_module_updates',
            ]
        );
    }
}
