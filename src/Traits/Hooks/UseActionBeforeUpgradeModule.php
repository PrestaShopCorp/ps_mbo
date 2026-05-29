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

use PrestaShop\Module\Mbo\Helpers\ErrorHelper;
use PrestaShop\Module\Mbo\Traits\HaveAddonsInstall;
use PrestaShop\PrestaShop\Adapter\Cache\Clearer\SymfonyCacheClearer;
use PrestaShop\PrestaShop\Adapter\Module\ModuleDataProvider;
use PrestaShop\PrestaShop\Core\File\Exception\FileNotFoundException;
use PrestaShop\PrestaShop\Core\Module\SourceHandler\SourceHandlerNotFoundException;

if (!defined('_PS_VERSION_')) {
    exit;
}

trait UseActionBeforeUpgradeModule
{
    use HaveAddonsInstall;
    /**
     * Hook actionBeforeUpgradeModule.
     *
     * @throws SourceHandlerNotFoundException
     * @throws FileNotFoundException
     */
    public function hookActionBeforeUpgradeModule(array $params): void
    {
        if (!empty($params['source'])) {
            return;
        }

        $moduleName = (string) $params['moduleName'];

        if ($this->isAlreadyDownloaded($moduleName)) {
            return;
        }

        $this->downloadModuleFromAddons($moduleName);

        // Clear the cache after download to force reload module services
        $this->purgeCache();
    }

    private function isAlreadyDownloaded(string $moduleName): bool
    {
        try {
            $moduleDataProvider = $this->getRequiredService(ModuleDataProvider::class);
            $dbData = $moduleDataProvider->findByName($moduleName);
            $onDiskModule = \Module::getInstanceByName($moduleName);

            return $onDiskModule !== false
                && isset($dbData['version'])
                && version_compare((string) $onDiskModule->version, $dbData['version'], '>');
        } catch (\Exception $e) {
            ErrorHelper::reportError($e);

            return false;
        }
    }

    private function purgeCache(): void
    {
        try {
            $cacheClearer = $this->getRequiredService(SymfonyCacheClearer::class);
        } catch (\Exception $e) {
            ErrorHelper::reportError($e);

            return;
        }

        $cacheClearer->clear();
    }
}
