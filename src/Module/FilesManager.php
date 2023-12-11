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
use PrestaShop\Module\Mbo\Addons\Provider\AddonsDataProvider;
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;
use PrestaShop\Module\Mbo\Module\Exception\UnexpectedModuleSourceContentException;
use PrestaShop\PrestaShop\Core\Module\SourceHandler\SourceHandlerFactory;
use PrestaShop\PrestaShop\Core\Module\SourceHandler\SourceHandlerNotFoundException;

class FilesManager
{
    /**
     * @var AddonsDataProvider
     */
    private $addonsDataProvider;
    /**
     * @var SourceHandlerFactory
     */
    private $sourceHandlerFactory;

    public function __construct(
        AddonsDataProvider $addonsDataProvider,
        SourceHandlerFactory $sourceHandlerFactory
    ) {
        $this->addonsDataProvider = $addonsDataProvider;
        $this->sourceHandlerFactory = $sourceHandlerFactory;
    }

    /**
     * @throws DownloadModuleException
     */
    public function downloadModule(int $moduleId): string
    {
        return $this->addonsDataProvider->downloadModule($moduleId);
    }

    /**
     * @throws UnexpectedModuleSourceContentException
     */
    public function installFromSource(string $source): void
    {
        try {
            $handler = $this->sourceHandlerFactory->getHandler($source);
            $handler->handle($source);
        } catch (Exception $e) {
            ErrorHelper::reportError($e);
            throw new UnexpectedModuleSourceContentException('The module download failed', 0, $e);
        }
    }

    /**
     * @throws SourceHandlerNotFoundException
     * @throws UnexpectedModuleSourceContentException
     */
    public function canInstallFromSource(string $source)
    {
        try {
            $this->sourceHandlerFactory->getHandler($source);
        } catch(SourceHandlerNotFoundException $e) {
            ErrorHelper::reportError($e);
            throw $e;
        } catch (Exception $e) {
            ErrorHelper::reportError($e);
            throw new UnexpectedModuleSourceContentException('The module download failed', 0, $e);
        }
    }

    public function deleteModuleDirectory(string $moduleName): void
    {
        $moduleDir = _PS_MODULE_DIR_ . $moduleName;

        if (!is_dir($moduleDir)) {
            return;
        }

        $this->deleteDirectoryRecursively($moduleDir);
    }

    private function deleteDirectoryRecursively(string $directory): void
    {
        if (is_dir($directory)) {
            $objects = scandir($directory);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($directory. DIRECTORY_SEPARATOR .$object) && !is_link($directory."/".$object)) {
                        $this->deleteDirectoryRecursively($directory . DIRECTORY_SEPARATOR . $object);
                    } else {
                        @unlink($directory . DIRECTORY_SEPARATOR . $object);
                    }
                }
            }
            @rmdir($directory);
        }
    }
}
