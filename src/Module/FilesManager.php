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

use PrestaShop\Module\Mbo\Addons\Provider\AddonsDataProvider;
use PrestaShop\PrestaShop\Core\File\Exception\FileNotFoundException;
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

    public function downloadModule(int $moduleId): string
    {
        return $this->addonsDataProvider->downloadModule($moduleId);
    }

    /**
     * @throws SourceHandlerNotFoundException
     * @throws FileNotFoundException
     */
    public function installFromZip(string $moduleZip): void
    {
        if (!file_exists($moduleZip)) {
            throw new FileNotFoundException('Unable to find module zip file given');
        }

        $handler = $this->sourceHandlerFactory->getHandler($moduleZip);
        $handler->handle($moduleZip);
    }

    /**
     * @param \stdClass $apiModule
     */
    public function deleteModuleDirectory(\stdClass $apiModule): void
    {
        $moduleDir = _PS_MODULE_DIR_ . $apiModule->name;

        if (!is_dir($moduleDir)) {
            return;
        }

        array_map('unlink', glob($moduleDir . DIRECTORY_SEPARATOR . '*.*'));
        @rmdir($moduleDir);
    }
}
