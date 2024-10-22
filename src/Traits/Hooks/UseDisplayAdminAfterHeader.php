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

use Cache;
use Configuration;
use Context;
use Exception;
use Language;
use PrestaShop\Module\Mbo\Distribution\Config\Command\VersionChangeApplyConfigCommand;
use PrestaShop\Module\Mbo\Distribution\Config\CommandHandler\VersionChangeApplyConfigCommandHandler;
use PrestaShop\Module\Mbo\Exception\ExpectedServiceNotFoundException;
use PrestaShop\Module\Mbo\Helpers\Config;
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;
use PrestaShop\Module\Mbo\Service\View\ContextBuilder;
use PrestaShop\PrestaShop\Core\Domain\Employee\Exception\EmployeeException;
use PrestaShop\PrestaShop\Core\Exception\CoreException;
use Shop;
use Tab;
use Tools;
use Twig\Environment;

trait UseDisplayAdminAfterHeader
{
    /**
     * Hook displayAdminAfterHeader.
     * Adds content in BackOffice after header section
     *
     * @return string
     */
    public function hookDisplayAdminAfterHeader(): string
    {
        $this->ensureModuleIsCorrectlySetUp();

        $shouldDisplayMboUserExplanation = $this->shouldDisplayMboUserExplanation();
        $shouldDisplayModuleManagerMessage = $this->shouldDisplayModuleManagerMessage();

        if (!$shouldDisplayMboUserExplanation && !$shouldDisplayModuleManagerMessage) {
            return '';
        }

        if ($shouldDisplayMboUserExplanation) {
            return $this->renderMboUserExplanation();
        }

        if ($shouldDisplayModuleManagerMessage) {
            return $this->renderModuleManagerMessage();
        }

        return '';
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function bootUseDisplayAdminAfterHeader(): void
    {
        if (method_exists($this, 'addAdminControllerMedia')) {
            $this->addAdminControllerMedia('loadMediaDisplayAdminAfterHeader');
        }
    }

    /**
     * Add JS and CSS file
     *
     * @return void
     *
     * @see \PrestaShop\Module\Mbo\Traits\Hooks\UseActionAdminControllerSetMedia
     */
    protected function loadMediaDisplayAdminAfterHeader(): void
    {
        if ($this->shouldDisplayMboUserExplanation()) {
            $this->context->controller->addJs(
                sprintf('%sviews/js/mbo-user-explanation.js?v=%s', $this->getPathUri(), $this->version)
            );
            $this->context->controller->addCSS(
                sprintf('%sviews/css/mbo-user-explanation.css?v=%s', $this->getPathUri(), $this->version)
            );
        }
    }

    private function renderMboUserExplanation(): string
    {
        try {
            /** @var Environment $twig */
            $twig = $this->get('twig');

            return $twig->render(
                '@Modules/ps_mbo/views/templates/hook/twig/explanation_mbo_employee.html.twig', [
                    'title' => $this->trans(
                        'Why is there a "PrestaShop Marketplace" employee?',
                        [],
                        'Modules.Mbo.Global'
                    ),
                    'message' => $this->trans('MBO employee explanation', [], 'Modules.Mbo.Global'),
                ]
            );
        } catch (Exception $e) {
            ErrorHelper::reportError($e);

            return '';
        }
    }

    private function renderModuleManagerMessage(): string
    {
        try {
            /** @var Environment $twig */
            $twig = $this->get('twig');
            /** @var ContextBuilder $contextBuilder */
            $contextBuilder = $this->get('mbo.cdc.context_builder');

            if (null === $contextBuilder || null === $twig) {
                throw new ExpectedServiceNotFoundException(
                    'Some services not found in UseDisplayAdminAfterHeader'
                );
            }

            return $twig->render(
                '@Modules/ps_mbo/views/templates/hook/twig/module_manager_message.html.twig', [
                    'shop_context' => $contextBuilder->getViewContext(),
                    'title' => $this->trans(
                        'Why is there a "PrestaShop Marketplace" employee?',
                        [],
                        'Modules.Mbo.Global'
                    ),
                    'message' => $this->trans('MBO employee explanation', [], 'Modules.Mbo.Global'),
                ]
            );
        } catch (Exception $e) {
            ErrorHelper::reportError($e);

            return '';
        }
    }

    private function shouldDisplayMboUserExplanation(): bool
    {
        if (Tools::getValue('controller') !== 'AdminEmployees') {
            return false;
        }

        try {
            $requestStack = $this->get('request_stack');
            if (null === $requestStack || null === $request = $requestStack->getCurrentRequest()) {
                throw new Exception('Unable to get request');
            }
        } catch (Exception $e) {
            ErrorHelper::reportError($e);

            return false;
        }

        // because admin_employee_index and admin_employee_edit are in the same controller AdminEmployees
        return 'admin_employees_index' === $request->get('_route');
    }

    private function shouldDisplayModuleManagerMessage(): bool
    {
        if (
            !in_array(
                Tools::getValue('controller'),
                [
                    'AdminModulesManage',
                    'AdminModulesNotifications',
                    'AdminModulesUpdates',
                ]
            )
        ) {
            return false;
        }

        try {
            $requestStack = $this->get('request_stack');
            if (null === $requestStack || null === $request = $requestStack->getCurrentRequest()) {
                throw new Exception('Unable to get request');
            }
        } catch (Exception $e) {
            ErrorHelper::reportError($e);

            return false;
        }

        // because admin_employee_index and admin_employee_edit are in the same controller AdminEmployees
        return in_array($request->get('_route'), [
            'admin_module_manage',
            'admin_module_notification',
            'admin_module_updates',
        ]);
    }

    public function ensureModuleIsCorrectlySetUp(): void
    {
        $whitelistedControllers = [
            'AdminPsMboModule',
            'AdminPsMboModuleParent',
            'AdminPsMboRecommended',
            'apiPsMbo',
            'apiSecurityPsMbo',
            'AdminModulesManage',
        ];
        $controllerName = Tools::getValue('controller');

        if (!in_array($controllerName, $whitelistedControllers)) {
            return;
        }

        $this->translateTabsIfNeeded();
        $this->ensureShopIsRegistered();
        $this->ensureShopIsUpdated();
        $this->ensureApiConfigIsApplied();

        if (self::checkModuleStatus()) { // If the module is not active, config values are not set yet
            $this->ensureApiUserExistAndIsLogged($controllerName);
        }
    }

    private function ensureShopIsRegistered(): void
    {
        if (!file_exists($this->moduleCacheDir . 'registerShop.lock') && $this->ensureShopIsConfigured()) {
            return;
        }
        $this->registerShop();
    }

    private function ensureShopIsConfigured(): bool
    {
        $configurationList = [];
        $configurationList['PS_MBO_SHOP_ADMIN_UUID'] = false;
        $configurationList['PS_MBO_SHOP_ADMIN_MAIL'] = false;
        $configurationList['PS_MBO_LAST_PS_VERSION_API_CONFIG'] = false;

        foreach ($configurationList as $name => $value) {
            if (Configuration::hasKey($name)) {
                $configurationList[$name] = true;
            }
        }

        if ($configurationList['PS_MBO_LAST_PS_VERSION_API_CONFIG']
            && $configurationList['PS_MBO_SHOP_ADMIN_MAIL']
            && $configurationList['PS_MBO_SHOP_ADMIN_UUID']) {
            return true;
        }

        foreach (Shop::getShops(false, null, true) as $shopId) {
            foreach ($configurationList as $name => $value) {
                if (Configuration::hasKey($name, null, null, (int) $shopId)) {
                    $configurationList[$name] = true;
                }
            }
        }

        return $configurationList['PS_MBO_LAST_PS_VERSION_API_CONFIG']
            && $configurationList['PS_MBO_SHOP_ADMIN_MAIL']
            && $configurationList['PS_MBO_SHOP_ADMIN_UUID'];
    }

    private function ensureShopIsUpdated(): void
    {
        if (!file_exists($this->moduleCacheDir . 'updateShop.lock')) {
            return;
        }
        $this->updateShop();
    }

    private function ensureApiConfigIsApplied(): void
    {
        try {
            /** @var DoctrineProvider $cacheProvider */
            $cacheProvider = $this->get('doctrine.cache.provider');
        } catch (Exception $e) {
            ErrorHelper::reportError($e);
            $cacheProvider = false;
        }
        $cacheKey = 'mbo_last_ps_version_api_config_check';

        if ($cacheProvider && $cacheProvider->contains($cacheKey)) {
            $lastCheck = $cacheProvider->fetch($cacheKey);

            $timeSinceLastCheck = (strtotime('now') - strtotime($lastCheck)) / (60 * 60);
            if ($timeSinceLastCheck < 3) { // If last check happened lss than 3hrs, do nothing
                return;
            }
        }

        if (_PS_VERSION_ === Config::getLastPsVersionApiConfig()) {
            // Config already applied for this version of PS
            return;
        }

        // Apply the config for the new PS version
        $command = new VersionChangeApplyConfigCommand(_PS_VERSION_, $this->version);
        try {
            /** @var VersionChangeApplyConfigCommandHandler $configApplyHandler */
            $configApplyHandler = $this->get('mbo.distribution.api_version_change_config_apply_handler');
        } catch (Exception $e) {
            ErrorHelper::reportError($e);

            return;
        }
        $configApplyHandler->handle($command);

        // Update the PS_MBO_LAST_PS_VERSION_API_CONFIG
        Configuration::updateValue('PS_MBO_LAST_PS_VERSION_API_CONFIG', _PS_VERSION_);

        if ($cacheProvider) {
            $cacheProvider->save($cacheKey, (new \DateTime())->format('Y-m-d H:i:s'), 0);
        }
    }

    /**
     * @param string|bool $controllerName
     * @param array $params
     *
     * @return void
     *
     * @throws EmployeeException
     * @throws CoreException
     * @throws Exception
     */
    private function ensureApiUserExistAndIsLogged($controllerName): void
    {
        if ($controllerName !== 'apiPsMbo') {
            return;
        }
        $apiUser = null;
        $apiUser = $this->getAdminAuthenticationProvider()->ensureApiUserExistence();

        if (!$apiUser) {
            return;
        }

        if (!$apiUser->isLoggedBack()) { // Log the user
            $cookie = $this->getAdminAuthenticationProvider()->apiUserLogin($apiUser);

            Cache::clean('isLoggedBack' . $apiUser->id);

            $this->context->employee = $apiUser;
            $this->context->cookie = $cookie;
            Context::getContext()->cookie = $cookie;
        }
    }

    private function translateTabsIfNeeded(): void
    {
        $lockFile = $this->moduleCacheDir . 'translate_tabs.lock';
        if (!file_exists($lockFile)) {
            return;
        }

        $moduleTabs = Tab::getCollectionFromModule($this->name);
        $languages = Language::getLanguages(false);

        /**
         * @var Tab $tab
         */
        foreach ($moduleTabs as $tab) {
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

        @unlink($lockFile);
    }
}
