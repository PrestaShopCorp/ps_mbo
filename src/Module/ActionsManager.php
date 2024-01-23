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

use Exception;
use PrestaShop\Module\Mbo\Addons\Exception\DownloadModuleException;
use PrestaShop\Module\Mbo\Helpers\Config;
use PrestaShop\Module\Mbo\Module\Exception\UnexpectedModuleSourceContentException;
use PrestaShop\Module\Mbo\Module\SourceRetriever\AddonsUrlSourceRetriever;
use PrestaShop\PrestaShop\Core\Module\SourceHandler\SourceHandlerNotFoundException;

class ActionsManager
{
    /**
     * @var FilesManager
     */
    private $filesManager;

    /**
     * @var Repository
     * @TODO : Not needed anymore
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
     * @throws UnexpectedModuleSourceContentException
     * @throws DownloadModuleException
     */
    public function install(int $moduleId): void
    {
        $moduleZip = $this->filesManager->downloadModule($moduleId);

        $this->filesManager->installFromSource($moduleZip);
    }

    /**
     * @throws DownloadModuleException
     */
    public function downloadModule(int $moduleId): string
    {
        return $this->filesManager->downloadModule($moduleId);
    }

    /**
     * @throws UnexpectedModuleSourceContentException
     * @throws SourceHandlerNotFoundException
     */
    public function downloadAndReplaceModuleFiles(string $moduleName, string $source): void
    {
        if (
            AddonsUrlSourceRetriever::assertIsAddonsUrl($source)
            && strpos($source, 'shop_url') === false
        ) {
            $source .= '&shop_url=' . Config::getShopUrl();
        }

        $this->filesManager->canInstallFromSource($source);

        if ('ps_mbo' === $moduleName) {
            $this->filesManager->deleteModuleDirectory($moduleName);
        }

        $this->filesManager->installFromSource($source);
    }
}
