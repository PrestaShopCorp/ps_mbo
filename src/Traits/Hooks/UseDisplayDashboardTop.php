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
use Hook;
use PrestaShop\Module\Mbo\Exception\ExpectedServiceNotFoundException;
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;
use PrestaShop\Module\Mbo\Tab\TabInterface;
use PrestaShopDatabaseException;
use PrestaShopException;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use ToolsCore as Tools;

trait UseDisplayDashboardTop
{
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

    protected $controllersWithRecommendedModules = [
        TabInterface::RECOMMENDED_BUTTON_TYPE => TabInterface::TABS_WITH_RECOMMENDED_MODULES_BUTTON,
        TabInterface::RECOMMENDED_AFTER_CONTENT_TYPE => TabInterface::TABS_WITH_RECOMMENDED_MODULES_AFTER_CONTENT,
    ];

    /**
     * @return void
     *
     * @throws Exception
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
     * @throws Exception
     */
    public function hookDisplayDashboardTop(): string
    {
        // Check if this page has already been processed by the hook to avoid duplicate content
        if ($this->alreadyProcessedPage) {
            return '';
        }
        $this->alreadyProcessedPage = true;

        $values = Tools::getAllValues();
        $moduleCacheDir = sprintf('%s/var/modules/ps_mbo/', rtrim(_PS_ROOT_DIR_, '/'));
        $createApiUserLockFile = $moduleCacheDir . 'createApiUser.lock';

        if (
            isset($values['controller']) &&
            ($values['controller'] === 'AdminPsMboModule') &&
            file_exists($createApiUserLockFile)
        ) {
            return $this->displayFailedApiUser();
        }

        //Check if we are on configuration page & if the module needs to have a push on this page
        $shouldDisplayMessageInConfigPage = isset($values['controller'])
            && ($values['controller'] === self::$ADMIN_MODULES_CONTROLLER)
            && isset($values['configure'])
            && in_array($values['configure'], self::$MODULES_WITH_CONFIGURATION_PUSH);

        return $shouldDisplayMessageInConfigPage
            ? $this->displayPushOnConfigurationPage($values['configure'])
            : $this->displayRecommendedModules($values['controller'] ?? '');
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function useDisplayDashboardTopExtraOperations(): void
    {
        $hookName = 'actionMboRecommendedModules';

        $id_hook = Hook::getIdByName($hookName, false);
        if (!$id_hook) {
            $new_hook = new Hook();
            $new_hook->name = pSQL($hookName);
            $new_hook->title = '';
            $new_hook->position = true;
            $new_hook->add();
        }
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
                    'linkText' => $this->trans('PrestaShop Addons Marketplace', [], 'Modules.Mbo.Global'),
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
                    'linkText' => $this->trans('PrestaShop Addons Marketplace', [], 'Modules.Mbo.Global'),
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
                    'linkText' => $this->trans('PrestaShop Addons Marketplace', [], 'Modules.Mbo.Global'),
                ]);
        }

        return $this->fetch('module:ps_mbo/views/templates/hook/push-configuration.tpl');
    }

    private function displayFailedApiUser(): string
    {
        try {
            /** @var \Twig\Environment $twig */
            $twig = $this->get('twig');

            /** @var Router $router*/
            $router = $this->get('router');

            if (null === $twig || null === $router) {
                throw new ExpectedServiceNotFoundException(
                    'Some services not found in UseDisplayDashboardTop'
                );
            }

            return $twig->render(
                '@Modules/ps_mbo/views/templates/hook/twig/failed-api-user.html.twig', [
                    'module_manager_link' => $router->generate('admin_module_manage'),
                ]
            );
        } catch (Exception $e) {
            ErrorHelper::reportError($e);

            return '';
        }
    }

    /**
     * Compute & include data with recommended modules when needed
     *
     * @throws Exception
     */
    protected function displayRecommendedModules(string $controller): string
    {
        $recommendedModulesDisplayed = true;

        // Ask to modules if recommended modules should be displayed in this context
        Hook::exec('actionMboRecommendedModules', [
            'recommendedModulesDisplayed' => &$recommendedModulesDisplayed,
            'controller' => $controller,
        ]);


        $afterContentType = TabInterface::RECOMMENDED_AFTER_CONTENT_TYPE;
        $recommendedButtonType = TabInterface::RECOMMENDED_BUTTON_TYPE;

        // We want to "hide" recommended modules from this controller
        if (!$recommendedModulesDisplayed) {
            // If we are trying to display as button, hide the button
            if (
                in_array(
                    $controller,
                    $this->controllersWithRecommendedModules[$recommendedButtonType]
                )
            ) {
                return '';
            } elseif (
                in_array(
                    $controller,
                    $this->controllersWithRecommendedModules[$afterContentType]
                )
            ) {

                // We are trying to display after content, so move them to button adn remove from after content
                foreach ($this->controllersWithRecommendedModules[$afterContentType] as $k => $afterContentController) {
                    if ($afterContentController === $controller) {
                        unset($this->controllersWithRecommendedModules[$afterContentType][$k]);
                        break;
                    }
                }
                $this->controllersWithRecommendedModules[TabInterface::RECOMMENDED_BUTTON_TYPE][] = $controller;
            }
        }

        $shouldAttachRecommendedModulesAfterContent = $this->shouldAttachRecommendedModules($afterContentType);
        $shouldAttachRecommendedModulesButton = $this->shouldAttachRecommendedModules($recommendedButtonType);

        if (!$shouldAttachRecommendedModulesAfterContent && !$shouldAttachRecommendedModulesButton) {
            return '';
        }

        try {
            /** @var UrlGeneratorInterface $router */
            $router = $this->get('router');
            if (null === $router) {
                throw new ExpectedServiceNotFoundException(
                    'Some services not found in UseDisplayDashboardTop'
                );
            }

            $recommendedModulesUrl = $router->generate(
                'admin_mbo_recommended_modules',
                [
                    'tabClassName' => Tools::getValue('controller'),
                    'recommendation_format' => $shouldAttachRecommendedModulesButton ? 'modal' : 'card',
                ]
            );
        } catch (Exception $exception) {
            // Avoid fatal errors on ServiceNotFoundException
            ErrorHelper::reportError($exception);
            return '';
        }

        $this->smarty->assign([
            'shouldAttachRecommendedModulesAfterContent' => $shouldAttachRecommendedModulesAfterContent,
            'shouldAttachRecommendedModulesButton' => $shouldAttachRecommendedModulesButton,
            'shouldUseLegacyTheme' => $this->isAdminLegacyContext(),
            'recommendedModulesCloseTranslated' => $this->trans('Close', [], 'Admin.Actions'),
            'recommendedModulesUrl' => $recommendedModulesUrl,
            'recommendedModulesTitleTranslated' => $this->getRecommendedModulesButtonTitle($controller),
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
        if ($type === TabInterface::RECOMMENDED_BUTTON_TYPE) {
            $modules = $this->controllersWithRecommendedModules[TabInterface::RECOMMENDED_BUTTON_TYPE];
        } elseif ($type === TabInterface::RECOMMENDED_AFTER_CONTENT_TYPE) {
            $modules = $this->controllersWithRecommendedModules[TabInterface::RECOMMENDED_AFTER_CONTENT_TYPE];
        } else {
            return false;
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

        if (
            $this->shouldAttachRecommendedModules(TabInterface::RECOMMENDED_BUTTON_TYPE)
            || $this->shouldAttachRecommendedModules(TabInterface::RECOMMENDED_AFTER_CONTENT_TYPE)
        ) {
            $this->context->controller->addCSS($this->getPathUri() . 'views/css/recommended-modules.css');
        }
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
}
