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

use PrestaShop\Module\Mbo\Distribution\Client;

trait HaveShopOnExternalService
{
    private function registerShop(): void
    {
        $this->callServiceWithLockFile('registerShop');
    }

    private function updateShop(): void
    {
        $this->callServiceWithLockFile('updateShop');
    }

    private function callServiceWithLockFile(string $method): void
    {
        $lockFile = $this->moduleCacheDir . $method . '.lock';

        try {
            /** @var Client $distributionApi */
            $distributionApi = $this->getService('mbo.cdc.client.distribution_api');
            if (!method_exists($distributionApi, $method)) {
                return;
            }

            $token = $this->getAdminAuthenticationProvider()->getAdminToken();

            $distributionApi->{$method}($token);

            if (file_exists($lockFile)) {
                unlink($lockFile);
            }
        } catch (\Exception $exception) {
            // Create the lock file
            if (!file_exists($lockFile)) {
                if (!is_dir($this->moduleCacheDir)) {
                    mkdir($this->moduleCacheDir);
                }
                $f = fopen($lockFile, 'w+');
                fclose($f);
            }
        }
    }
}
