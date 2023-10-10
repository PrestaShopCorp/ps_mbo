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

namespace PrestaShop\Module\Mbo\Module;

use PrestaShop\Module\Mbo\Helpers\Config;
use PrestaShop\Module\Mbo\Module\Exception\ModuleNewVersionNotFoundException;
use PrestaShop\Module\Mbo\Module\Exception\UnexpectedModuleSourceContentException;
use PrestaShop\Module\Mbo\Module\SourceRetriever\AddonsUrlSourceRetriever;
use PrestaShop\Module\Mbo\Module\SourceRetriever\SourceRetrieverInterface;
use PrestaShop\PrestaShop\Core\File\Exception\FileNotFoundException;
use PrestaShop\PrestaShop\Core\Module\Exception\ModuleErrorException;
use PrestaShop\PrestaShop\Core\Module\SourceHandler\SourceHandlerNotFoundException;

class ActionsManager
{
    /**
     * @var FilesManager
     */
    private $filesManager;

    /**
     * @var Repository
     */
    private $moduleRepository;

    public function __construct(
        FilesManager $filesManager,
        Repository $moduleRepository
    ) {
        $this->filesManager = $filesManager;
        $this->moduleRepository = $moduleRepository;
    }

    /**
     * @param int $moduleId
     *
     * @throws SourceHandlerNotFoundException
     * @throws FileNotFoundException
     */
    public function install(int $moduleId): void
    {
        $moduleZip = $this->filesManager->downloadModule($moduleId);

        $this->filesManager->installFromSource($moduleZip);
    }

    /**
     * @throws UnexpectedModuleSourceContentException
     * @throws ModuleNewVersionNotFoundException
     */
    public function downloadAndReplaceModuleFiles(\stdClass $module, string $source): void
    {
        if (is_string($source) && AddonsUrlSourceRetriever::assertIsAddonsUrl($source) && strpos($source, 'shop_url') === false) {
            $source .= '&shop_url=' . Config::getShopUrl();
        }

        $this->filesManager->deleteModuleDirectory($module);

        $this->filesManager->installFromSource($source);
    }

    /**
     * @param string $moduleName
     *
     * @return \stdClass|null
     */
    public function findVersionForUpdate(string $moduleName): ?\stdClass
    {
        $db = \Db::getInstance();
        $request = 'SELECT `version` FROM `' . _DB_PREFIX_ . "module` WHERE name='" . $moduleName . "'";

        /** @var string|false $moduleCurrentVersion */
        $moduleCurrentVersion = $db->getValue($request);

        if (!$moduleCurrentVersion) {
            return null;
        }
        // We need to clear cache to get fresh data from addons
        $this->moduleRepository->clearCache();

        $module = $this->moduleRepository->getApiModule($moduleName);

        if (null === $module) {
            return null;
        }

        $versionAvailable = (string) $module->version_available;

        // If the current installed version is greater or equal than the one returned by Addons, do nothing
        if (version_compare($versionAvailable, $moduleCurrentVersion, 'gt')) {
            return $module;
        }

        return null;
    }
}
