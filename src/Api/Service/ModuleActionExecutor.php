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
