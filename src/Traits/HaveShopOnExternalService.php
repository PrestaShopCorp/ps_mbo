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
use Ramsey\Uuid\Uuid;

trait HaveShopOnExternalService
{
    /**
     * Register a shop for online services delivered by API.
     * So the module can correctly process actions (download, install, update..) on. modules
     *
     * @return void
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function registerShop(): void
    {
        // We have to install config here because the register method is called by parent::install -> module->enable
        // Furthermore, this make a check and ensure existence in case of accidental removal
        $this->installConfiguration();
        $this->callServiceWithLockFile('registerShop');
    }

    /**
     * Update the shop in only services
     *
     * @return void
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function updateShop(): void
    {
        $this->callServiceWithLockFile('updateShop');
    }

    /**
     * Unregister a shop of online services delivered by API.
     * When the module is disabled or uninstalled, remove it from online services
     *
     * @return void
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function unregisterShop(): void
    {
        $token = $this->getAdminAuthenticationProvider()->getAdminToken();

        /** @var Client $distributionApi */
        $distributionApi = $this->getService('mbo.cdc.client.distribution_api');

        try {
            $distributionApi->unregisterShop($token);
        } catch (\Exception $e) {
            // Do nothing here, the exception is caught to avoid displaying an error to the client
            // Furthermore, the operation can't be tried again later as the module is now disabled or uninstalled
        }
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
            dd($exception);
            if (!file_exists($lockFile)) {
                if (!is_dir($this->moduleCacheDir)) {
                    mkdir($this->moduleCacheDir);
                }
                $f = fopen($lockFile, 'w+');
                fclose($f);
            }
        }
    }

    /**
     * Install configuration for each shop
     *
     * @return bool
     */
    private function installConfiguration(): bool
    {
        $result = true;

        // Values generated
        $adminUuid = Uuid::uuid4()->toString();
        $this->configurationList['PS_MBO_SHOP_ADMIN_UUID'] = $adminUuid;
        $this->configurationList['PS_MBO_SHOP_ADMIN_MAIL'] = sprintf('mbo-%s@prestashop.com', $adminUuid);

        foreach (\Shop::getShops(false, null, true) as $shopId) {
            foreach ($this->configurationList as $name => $value) {
                if (false === \Configuration::hasKey($name, null, null, (int) $shopId)) {
                    $result = $result && (bool) \Configuration::updateValue(
                            $name,
                            $value,
                            false,
                            null,
                            (int) $shopId
                        );
                }
            }
        }

        return $result;
    }
}
