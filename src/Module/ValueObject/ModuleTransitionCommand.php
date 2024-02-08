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

namespace PrestaShop\Module\Mbo\Module\ValueObject;

use PrestaShop\Module\Mbo\Module\Exception\UnknownModuleTransitionCommandException;
use PrestaShop\Module\Mbo\Module\Workflow\TransitionInterface;

class ModuleTransitionCommand
{
    public const MODULE_COMMAND_INSTALL = 'module.install';
    public const MODULE_COMMAND_ENABLE = 'module.enable';
    public const MODULE_COMMAND_DISABLE = 'module.disable';
    public const MODULE_COMMAND_MOBILE_ENABLE = 'module.mobile_enable';
    public const MODULE_COMMAND_MOBILE_DISABLE = 'module.mobile_disable';
    public const MODULE_COMMAND_RESET = 'module.reset';
    public const MODULE_COMMAND_CONFIGURE = 'module.configure';
    public const MODULE_COMMAND_UPGRADE = 'module.upgrade';
    public const MODULE_COMMAND_UNINSTALL = 'module.uninstall';
    public const MODULE_COMMAND_DOWNLOAD = 'module.download';

    public const MAPPING_TRANSITION_COMMAND_TARGET_STATUS = [
        self::MODULE_COMMAND_INSTALL => TransitionInterface::STATUS_ENABLED__MOBILE_ENABLED,
        self::MODULE_COMMAND_ENABLE => 'computed',
        self::MODULE_COMMAND_DISABLE => 'computed',
        self::MODULE_COMMAND_MOBILE_ENABLE => 'computed',
        self::MODULE_COMMAND_MOBILE_DISABLE => 'computed',
        self::MODULE_COMMAND_CONFIGURE => TransitionInterface::STATUS_CONFIGURED,
        self::MODULE_COMMAND_RESET => TransitionInterface::STATUS_RESET,
        self::MODULE_COMMAND_UPGRADE => TransitionInterface::STATUS_UPGRADED,
        self::MODULE_COMMAND_UNINSTALL => TransitionInterface::STATUS_UNINSTALLED,
    ];

    public const MODULE_COMMANDS = [
        self::MODULE_COMMAND_INSTALL,
        self::MODULE_COMMAND_ENABLE,
        self::MODULE_COMMAND_DISABLE,
        self::MODULE_COMMAND_MOBILE_ENABLE,
        self::MODULE_COMMAND_MOBILE_DISABLE,
        self::MODULE_COMMAND_RESET,
        self::MODULE_COMMAND_CONFIGURE,
        self::MODULE_COMMAND_UPGRADE,
        self::MODULE_COMMAND_UNINSTALL,
        self::MODULE_COMMAND_DOWNLOAD,
    ];

    /**
     * @var string
     */
    private $command;

    /**
     * @param string $command
     *
     * @throws UnknownModuleTransitionCommandException
     */
    public function __construct(string $command)
    {
        if (!in_array($command, self::MODULE_COMMANDS, true)) {
            throw new UnknownModuleTransitionCommandException(
                sprintf('Module transition command given %s is unknown.', $command)
            );
        }

        $this->command = $command;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->command;
    }
}
