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

use PrestaShop\PrestaShop\Core\File\Exception\FileNotFoundException;
use PrestaShop\PrestaShop\Core\Module\ModuleRepository as CoreModuleRepository;
use PrestaShop\PrestaShop\Core\Module\SourceHandler\SourceHandlerNotFoundException;

class ActionsManager
{
    /**
     * @var FilesManager
     */
    private $filesManager;

    /**
     * @var CoreModuleRepository
     */
    private $coreModuleRepository;

    /**
     * @var Repository
     */
    private $moduleRepository;

    public function __construct(
        FilesManager $filesManager,
        CoreModuleRepository $coreModuleRepository,
        Repository $moduleRepository
    ) {
        $this->filesManager = $filesManager;
        $this->coreModuleRepository = $coreModuleRepository;
        $this->moduleRepository = $moduleRepository;
    }

    /**
     * @param Module $module
     *
     * @throws SourceHandlerNotFoundException
     * @throws FileNotFoundException
     */
    public function install(Module $module): void
    {
        $moduleZip = $this->filesManager->downloadModule(
            (int) $module->get('id')
        );

        $this->filesManager->installFromZip($moduleZip);
    }

    /**
     * Right now, in MBO, upgrading a module results on deleting previous files and reinstall the module
     * The ModuleManager will do the rest
     * In the future, if it's changes, just duplicate the content of the "install" method and adjust
     *
     * @param Module $module
     *
     * @throws SourceHandlerNotFoundException
     * @throws FileNotFoundException
     */
    public function upgrade(Module $module): void
    {
        $this->filesManager->deleteModuleDirectory($module);

        $this->install($module);
    }

    /**
     * @param string $moduleName
     *
     * @return string|null
     */
    public function findVersionForUpdate(string $moduleName): ?string
    {
        $coreModule = $this->coreModuleRepository->getModule($moduleName);
        $moduleCurrentVersion = (string) $coreModule->get('version');

        // We need to clear cache to get fresh data from addons
        $this->moduleRepository->clearCache();
        /** @var Module $module */
        $module = $this->moduleRepository->getModule($moduleName);

        if (null === $module) {
            return null;
        }

        $versionAvailable = (string) $module->get('version_available');

        // If the current installed version is greater or equal than the one returned by Addons, do nothing
        if (version_compare($versionAvailable, $moduleCurrentVersion, 'gt')) {
            return $versionAvailable;
        }

        return null;
    }
}
