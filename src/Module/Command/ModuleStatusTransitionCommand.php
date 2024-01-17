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

namespace PrestaShop\Module\Mbo\Module\Command;

use PrestaShop\Module\Mbo\Module\Exception\UnknownModuleTransitionCommandException;
use PrestaShop\Module\Mbo\Module\ValueObject\ModuleTransitionCommand;

class ModuleStatusTransitionCommand
{
    /**
     * @var ModuleTransitionCommand
     */
    private $command;

    /**
     * @var string
     */
    private $moduleName;

    /**
     * @var int
     */
    private $moduleId;

    /**
     * @var string
     */
    private $moduleVersion;

    /**
     * @var string|null
     */
    private $source;

    /**
     * @throws UnknownModuleTransitionCommandException
     */
    public function __construct(
        string $command,
        string $moduleName,
        int $moduleId,
        string $moduleVersion,
        ?string $source = null
    ) {
        $this->command = new ModuleTransitionCommand($command);
        $this->moduleName = $moduleName;
        $this->moduleId = $moduleId;
        $this->moduleVersion = $moduleVersion;

        if ($command === ModuleTransitionCommand::MODULE_COMMAND_DOWNLOAD) {
            if ('undefined' === $source) {
                $source = null;
            }
            $this->source = $source;
        }
    }

    public function getCommand(): ModuleTransitionCommand
    {
        return $this->command;
    }

    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    public function getModuleId(): int
    {
        return $this->moduleId;
    }

    public function getModuleVersion(): string
    {
        return $this->moduleVersion;
    }

    /**
     * @return string|null
     */
    public function getSource(): ?string
    {
        return $this->source;
    }
}
