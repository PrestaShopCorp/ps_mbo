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

namespace PrestaShop\Module\Mbo\Api\Service;

use http\Exception\InvalidArgumentException;
use PrestaShop\Module\Mbo\Distribution\Config\Command\ConfigChangeCommand;
use PrestaShop\Module\Mbo\Distribution\Config\CommandHandler\ConfigChangeCommandHandler;
use PrestaShop\Module\Mbo\Distribution\Config\Exception\InvalidConfigException;
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;
use Tools;

class ConfigApplyExecutor implements ServiceExecutorInterface
{
    const SERVICE = 'config';

    /**
     * @var ConfigChangeCommandHandler
     */
    private $configChangeCommandHandler;

    public function __construct(ConfigChangeCommandHandler $configChangeCommandHandler)
    {
        $this->configChangeCommandHandler = $configChangeCommandHandler;
    }

    /**
     * {@inheritDoc}
     */
    public function canExecute(string $service): bool
    {
        return self::SERVICE === $service;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(...$parameters): array
    {
        if (!$parameters[0] instanceof \Module) {
            throw new InvalidArgumentException();
        }

        $module = $parameters[0];

        try {
            $config = json_decode(Tools::getValue('conf'), true);
        } catch (\JsonException $exception) {
            ErrorHelper::reportError($exception);
            throw new InvalidConfigException($exception->getMessage());
        }

        if ($config === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidConfigException('Config given is invalid. Please check the structure.');
        }

        $command = new ConfigChangeCommand(
            $config,
            _PS_VERSION_,
            $module->version
        );

        $this->configChangeCommandHandler->handle($command);

        return [
            'message' => 'Config successfully applied',
        ];
    }
}
