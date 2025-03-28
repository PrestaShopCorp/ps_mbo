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
use PrestaShop\Module\Mbo\Helpers\Config;
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;
use PrestaShop\PrestaShop\Core\Domain\Employee\Exception\EmployeeException;
use PrestaShop\PrestaShop\Core\Exception\CoreException;

trait UseActionDispatcherBefore
{
    /**
     * Hook actionDispatcherBefore.
     *
     * @throws EmployeeException
     * @throws CoreException
     */
    public function hookActionDispatcherBefore(array $params): void
    {
        $controllerName = \Tools::getValue('controller');

        $this->translateTabsIfNeeded();

        // Registration failed on install, retry it
        if (in_array($controllerName, [
            'AdminPsMboModuleParent',
            'AdminPsMboRecommended',
            'apiPsMbo',
        ])) {
            $this->ensureApiConfigIsApplied();
        }
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
