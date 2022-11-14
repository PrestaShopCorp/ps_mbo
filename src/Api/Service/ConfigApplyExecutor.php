<?php

namespace PrestaShop\Module\Mbo\Api\Service;

use http\Exception\InvalidArgumentException;
use PrestaShop\Module\Mbo\Distribution\Config\Command\ConfigChangeCommand;
use PrestaShop\Module\Mbo\Distribution\Config\CommandHandler\ConfigChangeCommandHandler;
use PrestaShop\Module\Mbo\Distribution\Config\Exception\InvalidConfigException;
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
            throw new InvalidConfigException($exception->getMessage());
        }

        if ($config === null && json_last_error() !== JSON_ERROR_NONE) {
            var_dump(Tools::getValue('conf'), gettype(Tools::getValue('conf')), $config, json_last_error_msg());
            throw new InvalidConfigException('Config given is invalid. Please check the structure.');
        }

        $command = new ConfigChangeCommand(
            $config,
            _PS_VERSION_,
            $module->version
        );

        $configCollection = $this->configChangeCommandHandler->handle($command);

        return [
            'message' => 'Config successfully applied',
        ];
    }
}
