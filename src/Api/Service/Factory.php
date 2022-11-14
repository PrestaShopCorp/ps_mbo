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

use PrestaShop\Module\Mbo\Api\Exception\UnknownServiceException;

final class Factory
{
    private const ALLOWED_SERVICES = [
        ModuleTransitionExecutor::SERVICE,
        ConfigApplyExecutor::SERVICE,
    ];

    /**
     * @param ServiceExecutorInterface[] $executors
     */
    public function __construct(array $executors)
    {
        $this->executors = $executors;
    }

    /**
     * @var ServiceExecutorInterface[]
     */
    private $executors;

    public function build(string $service): ServiceExecutorInterface
    {
        $this->assertServiceIsAllowed($service);

        foreach ($this->executors as $executor) {
            if ($executor->canExecute($service)) {
                return $executor;
            }
        }

        throw new UnknownServiceException('No executor have been found for that service');
    }

    private function assertServiceIsAllowed(string $service): void
    {
        if (!in_array($service, self::ALLOWED_SERVICES)) {
            throw new UnknownServiceException(sprintf('Unknown service given : %s', $service));
        }
    }
}
