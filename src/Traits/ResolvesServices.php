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

namespace PrestaShop\Module\Mbo\Traits;

use PrestaShop\Module\Mbo\Exception\ExpectedServiceNotFoundException;

if (!defined('_PS_VERSION_')) {
    exit;
}

trait ResolvesServices
{
    /**
     * Resolve a required service from the container.
     *
     * Use a `::class` reference when the service is registered by class name; PS-core
     * ids without a class alias (e.g. the `router` service) are passed as a string id,
     * in which case the caller must keep its own `@var` annotation for type narrowing.
     *
     * @template T of object
     *
     * @param class-string<T>|string $serviceId
     *
     * @return ($serviceId is class-string<T> ? T : object)
     *
     * @throws ExpectedServiceNotFoundException when the service is not found
     */
    protected function getRequiredService(string $serviceId): object
    {
        $service = $this->get($serviceId);

        if (null === $service) {
            throw new ExpectedServiceNotFoundException(sprintf('Service "%s" not found', $serviceId));
        }

        return $service;
    }
}
