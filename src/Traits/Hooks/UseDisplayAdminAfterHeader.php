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

use Doctrine\Common\Cache\CacheProvider;
use PrestaShop\Module\Mbo\Distribution\Config\Command\VersionChangeApplyConfigCommand;
use PrestaShop\Module\Mbo\Distribution\Config\CommandHandler\VersionChangeApplyConfigCommandHandler;
use PrestaShop\Module\Mbo\Exception\ExpectedServiceNotFoundException;
use PrestaShop\Module\Mbo\Helpers\Config;
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;
use PrestaShop\Module\Mbo\Service\View\ContextBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
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

        return $this->renderModuleManagerMessage();
    }

    /**
     * @return void
     *
     * @throws \Exception
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
     * @see UseActionAdminControllerSetMedia
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
            $twig = $this->get(Environment::class);

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
        } catch (\Exception $e) {
            ErrorHelper::reportError($e);

            return '';
        }
    }

    private function renderModuleManagerMessage(): string
    {
        try {
            /** @var Environment|null $twig */
            $twig = $this->get(Environment::class);
            /** @var ContextBuilder|null $contextBuilder */
            $contextBuilder = $this->get(ContextBuilder::class);

            if (null === $contextBuilder || null === $twig) {
                throw new ExpectedServiceNotFoundException('Some services not found in UseDisplayAdminAfterHeader');
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
        } catch (\Exception $e) {
            ErrorHelper::reportError($e);

            return '';
        }
    }

    private function shouldDisplayMboUserExplanation(): bool
    {
        if (\Tools::getValue('controller') !== 'AdminEmployees') {
            return false;
        }

        try {
            /** @var RequestStack|null $requestStack */
            $requestStack = $this->get(RequestStack::class);
            if (null === $requestStack || null === $request = $requestStack->getCurrentRequest()) {
                throw new \Exception('Unable to get request');
            }
        } catch (\Exception $e) {
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
                \Tools::getValue('controller'),
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
            /** @var RequestStack|null $requestStack */
            $requestStack = $this->get(RequestStack::class);
            if (null === $requestStack || null === $request = $requestStack->getCurrentRequest()) {
                throw new \Exception('Unable to get request');
            }
        } catch (\Exception $e) {
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

    private function ensureModuleIsCorrectlySetUp(): void
    {
        $this->translateTabsIfNeeded();

        $whitelistedControllers = [
            'AdminPsMboModule',
            'AdminPsMboModuleParent',
            'AdminPsMboRecommended',
            'apiPsMbo',
            'apiSecurityPsMbo',
            'AdminModulesManage',
        ];
        $controllerName = \Tools::getValue('controller');
        if (!in_array($controllerName, $whitelistedControllers)) {
            return;
        }

        $this->ensureApiConfigIsApplied();
    }

    private function ensureApiConfigIsApplied(): void
    {
        try {
            /** @var CacheProvider|null $cacheProvider */
            $cacheProvider = $this->get(CacheProvider::class);
        } catch (\Exception $e) {
            ErrorHelper::reportError($e);
            $cacheProvider = null;
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
            $configApplyHandler = $this->get(VersionChangeApplyConfigCommandHandler::class);
        } catch (\Exception $e) {
            ErrorHelper::reportError($e);

            return;
        }
        $configApplyHandler->handle($command);

        // Update the PS_MBO_LAST_PS_VERSION_API_CONFIG
        \Configuration::updateValue('PS_MBO_LAST_PS_VERSION_API_CONFIG', _PS_VERSION_);

        if ($cacheProvider) {
            $cacheProvider->save($cacheKey, (new \DateTime())->format('Y-m-d H:i:s'), 0);
        }
    }

    private function translateTabsIfNeeded(): void
    {
        $lockFile = $this->moduleCacheDir . 'translate_tabs.lock';
        if (!file_exists($lockFile)) {
            return;
        }

        $moduleTabs = \Tab::getCollectionFromModule($this->name);
        $languages = \Language::getLanguages(false);

        /**
         * @var \Tab $tab
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
