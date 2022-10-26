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

namespace PrestaShop\Module\Mbo\Distribution\Config;

use PrestaShop\Module\Mbo\Distribution\Config\Exception\InvalidConfigException;

class Config
{
    private const AVAILABLE_CONFIG_KEYS = [
        'menu_test',
        'theme_catalog_menu_link',
    ];

    private const AVAILABLE_CONFIG_VALUES = [
        'menu_test' => ['hide', 'show'],
        'theme_catalog_menu_link' => ['hide', 'show'],
    ];

    /**
     * @var string
     */
    private $configKey;

    /**
     * @var string
     */
    private $configValue;

    /**
     * @var string
     */
    private $psVersion;

    /**
     * @var string
     */
    private $mboVersion;

    /**
     * @throws InvalidConfigException
     */
    public function __construct(
        string $configKey,
        string $configValue,
        string $psVersion,
        string $mboVersion
    ) {
        $this->assertConfigKeyIsValid($configKey);
        $this->assertConfigValueIsValid($configKey, $configValue);
        $this->assertPsVersionIsValid($psVersion);
        $this->assertMboVersionIsValid($mboVersion);

        $this->configKey = $configKey;
        $this->configValue = $configValue;
        $this->psVersion = $psVersion;
        $this->mboVersion = $mboVersion;
    }

    /**
     * @return string
     */
    public function getConfigKey(): string
    {
        return $this->configKey;
    }

    /**
     * @return string
     */
    public function getConfigValue(): string
    {
        return $this->configValue;
    }

    /**
     * @return string
     */
    public function getPsVersion(): string
    {
        return $this->psVersion;
    }

    /**
     * @return string
     */
    public function getMboVersion(): string
    {
        return $this->mboVersion;
    }

    private function assertConfigKeyIsValid(string $configKey)
    {
        if (!in_array($configKey, self::AVAILABLE_CONFIG_KEYS)) {
            throw new InvalidConfigException();
        }
    }

    private function assertConfigValueIsValid(string $configKey, string $configValue)
    {
        if (
            isset(self::AVAILABLE_CONFIG_VALUES[$configKey])
            && !in_array($configValue, self::AVAILABLE_CONFIG_VALUES[$configKey])
        ) {
            throw new InvalidConfigException();
        }
    }

    private function assertPsVersionIsValid(string $configKey)
    {
        // No validation yet
    }

    private function assertMboVersionIsValid(string $configKey)
    {
        // No validation yet
    }
}
