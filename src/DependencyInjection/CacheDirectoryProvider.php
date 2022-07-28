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

namespace PrestaShop\Module\Mbo\DependencyInjection;

/**
 * Class responsible for returning cache directory path.
 */
class CacheDirectoryProvider
{
    /**
     * @var string PrestaShop version
     */
    private $psVersion;

    /**
     * @var string PrestaShop path
     */
    private $psPath;

    /**
     * @var bool PrestaShop Debug Mode
     */
    private $psIsDebugMode;

    public function __construct(string $psVersion, string $psPath, bool $psIsDebugMode)
    {
        $this->psVersion = $psVersion;
        $this->psPath = $psPath;
        $this->psIsDebugMode = $psIsDebugMode;
    }

    public function getPath(): string
    {
        if (defined('_PS_CACHE_DIR_')) {
            return constant('_PS_CACHE_DIR_');
        }

        $path = '/var/cache/' . $this->getEnvName();

        if (version_compare($this->psVersion, '1.7.0.0', '<')) {
            $path = '/cache';
        } elseif (version_compare($this->psVersion, '1.7.4.0', '<')) {
            $path = '/app/cache/' . $this->getEnvName();
        }

        return $this->psPath . $path;
    }

    public function isWritable(): bool
    {
        return is_writable($this->getPath());
    }

    public function isReadable(): bool
    {
        return is_readable($this->getPath());
    }

    private function getEnvName(): string
    {
        return $this->psIsDebugMode ? 'dev' : 'prod';
    }
}
