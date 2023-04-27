<?php

namespace PrestaShop\Module\Mbo\Api\Service;

use http\Exception\InvalidArgumentException;
use PrestaShop\Module\Mbo\Distribution\Client;
use PrestaShop\Module\Mbo\Module\Action\ActionInterface;

class ModuleActionExecutor implements ServiceExecutorInterface
{
    const SERVICE = 'module_action';

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
    public function execute(...$parameters): ?array
    {
        if (!$parameters[0] instanceof \Module) {
            throw new InvalidArgumentException();
        }

        $psMbo = $parameters[0];
        $actionToExecute = isset($parameters[1]) ? $parameters[1] : null;

        // Execute action processing
        if ($actionToExecute instanceof ActionInterface) {
            $psMbo->get('mbo.modules.actions.scheduler')->processAction($actionToExecute);
        }

        return null;
    }
}
