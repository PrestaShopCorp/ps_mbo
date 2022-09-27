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

use PrestaShop\Module\Mbo\Module\Exception\ModuleNewVersionNotFoundException;
use PrestaShop\Module\Mbo\Module\Exception\UnexpectedModuleSourceContentException;
use PrestaShop\Module\Mbo\Module\SourceRetriever\SourceRetrieverInterface;
use PrestaShop\PrestaShop\Core\File\Exception\FileNotFoundException;
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

    /**
     * @var SourceRetrieverInterface
     */
    private $sourceRetriever;

    public function __construct(
        FilesManager $filesManager,
        Repository $moduleRepository,
        SourceRetrieverInterface $sourceRetriever
    ) {
        $this->filesManager = $filesManager;
        $this->moduleRepository = $moduleRepository;
        $this->sourceRetriever = $sourceRetriever;
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

        $this->filesManager->installFromZip($moduleZip);
    }

    /**
     * @throws UnexpectedModuleSourceContentException
     * @throws ModuleNewVersionNotFoundException
     */
    public function downloadAndReplaceModuleFiles(\stdClass $module, ?string $source = null): void
    {
        $moduleZip = (null !== $source) ?
            $this->downloadModuleFromUrl($module, $source) :
            $this->downloadModuleFromModulesProvider($module);

        $this->filesManager->deleteModuleDirectory($module);

        $this->filesManager->installFromZip($moduleZip);
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

    private function downloadModuleFromModulesProvider(\stdClass $module): string
    {
        $moduleName = (string) $module->name;

        $module = $this->findVersionForUpdate($moduleName);
        if (null === $module) {
            throw new ModuleNewVersionNotFoundException(sprintf('A downloadable new version was not found for the module %s', $moduleName));
        }

        return $this->filesManager->downloadModule((int) $module->id);
    }

    private function downloadModuleFromUrl(\stdClass $module, string $source): string
    {
        $zipFilename = $this->sourceRetriever->get($source);
        if (!$this->sourceRetriever->validate($zipFilename, $module->name)) {
            throw new UnexpectedModuleSourceContentException(sprintf('The source given doesn\'t contains the expected module : %s', $module->name));
        }

        return $zipFilename;
    }
}
