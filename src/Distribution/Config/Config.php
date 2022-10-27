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

class Config
{
    /**
     * @var int|null
     */
    private $configId;

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
     * @var bool
     */
    private $applied;

    public function __construct(
        string $configKey,
        string $configValue,
        string $psVersion,
        string $mboVersion,
        bool $applied,
        ?int $configId = null
    ) {
        $this->configId = $configId;
        $this->configKey = $configKey;
        $this->configValue = $configValue;
        $this->psVersion = $psVersion;
        $this->mboVersion = $mboVersion;
        $this->applied = $applied;
    }

    public function getConfigId(): ?int
    {
        return $this->configId;
    }

    public function getConfigKey(): string
    {
        return $this->configKey;
    }

    public function getConfigValue(): string
    {
        return $this->configValue;
    }

    public function getPsVersion(): string
    {
        return $this->psVersion;
    }

    public function getMboVersion(): string
    {
        return $this->mboVersion;
    }

    public function isApplied(): bool
    {
        return $this->applied;
    }
}
