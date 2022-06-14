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

class ModuleTransitionCommand
{
    public const MODULE_COMMAND_INSTALL = 'module.install';

    public const MODULE_COMMANDS = [
        self::MODULE_COMMAND_INSTALL,
    ];

    /**
     * @var int
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
            throw new UnknownModuleTransitionCommandException(sprintf('Module transition command given %s is unknown.', $command));
        }

        $this->command = $command;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->command;
    }
}
