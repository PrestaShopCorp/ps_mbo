<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

namespace PrestaShop\Module\Mbo\Cache;

/**
 * Class responsible for returning cache directory path.
 */
class CacheDirectoryProvider
{
    /**
     * @var string PrestaShop path
     */
    private $psPath;

    /**
     * @var bool PrestaShop Debug Mode
     */
    private $psIsDebugMode;

    /**
     * @param string $psPath
     * @param bool $psIsDebugMode
     */
    public function __construct($psPath, $psIsDebugMode)
    {
        $this->psPath = $psPath;
        $this->psIsDebugMode = $psIsDebugMode;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        if (defined('_PS_CACHE_DIR_')) {
            return constant('_PS_CACHE_DIR_');
        }

        return $this->psPath . '/var/cache/' . $this->getEnvName();
    }

    /**
     * @return bool
     */
    public function isWritable()
    {
        return is_writable($this->getPath());
    }

    /**
     * @return bool
     */
    public function isReadable()
    {
        return is_readable($this->getPath());
    }

    /**
     * @return string
     */
    private function getEnvName()
    {
        if (defined('_PS_IN_TEST_')) {
            return 'test';
        }

        return $this->psIsDebugMode ? 'dev' : 'prod';
    }
}
