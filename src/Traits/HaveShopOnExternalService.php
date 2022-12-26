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

use Configuration;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use PrestaShop\Module\Mbo\Distribution\Client;
use PrestaShop\Module\Mbo\Distribution\Config\Command\ConfigChangeCommand;
use Ramsey\Uuid\Uuid;
use Shop;

trait HaveShopOnExternalService
{
    /**
     * Register a shop for online services delivered by API.
     * So the module can correctly process actions (download, install, update..) on. modules
     *
     * @return void
     */
    private function registerShop(): void
    {
        // We have to install config here because the register method is called by parent::install -> module->enable
        // Furthermore, this make a check and ensure existence in case of accidental removal
        $this->installConfiguration();
        $this->callServiceWithLockFile('registerShop');
        $this->syncApiConfig();
    }

    /**
     * Update the shop in online services
     *
     * @param array $params the params to send to the update method in Client
     *
     * @return void
     */
    public function updateShop(array $params = []): void
    {
        $this->callServiceWithLockFile('updateShop', $params);
    }

    /**
     * Unregister a shop of online services delivered by API.
     * When the module is disabled or uninstalled, remove it from online services
     *
     * @return void
     *
     * @throws GuzzleException
     */
    private function unregisterShop(): void
    {
        try {
            /** @var Client $distributionApi */
            $distributionApi = $this->getService('mbo.cdc.client.distribution_api');
            $distributionApi->setBearer($this->getAdminAuthenticationProvider()->getMboJWT());
            $distributionApi->unregisterShop();
        } catch (Exception $e) {
            // Do nothing here, the exception is caught to avoid displaying an error to the client
            // Furthermore, the operation can't be tried again later as the module is now disabled or uninstalled
        }
    }

    private function callServiceWithLockFile(string $method, array $params = []): void
    {
        $this->getAdminAuthenticationProvider()->clearCache();
        $lockFile = $this->moduleCacheDir . $method . '.lock';
        try {
            // If the module is installed via command line or somehow the ADMIN_DIR is not defined,
            // we ignore the shop registration, so it will be done at any action on the backoffice
            if (php_sapi_name() === 'cli' || !defined('_PS_ADMIN_DIR_')) {
                throw new Exception();
            }
            /** @var Client $distributionApi */
            $distributionApi = $this->getService('mbo.cdc.client.distribution_api');
            if (!method_exists($distributionApi, $method)) {
                return;
            }

            // Add the default params
            $params = array_merge($params, [
                'mbo_api_user_token' => $this->getAdminAuthenticationProvider()->getAdminToken(),
                'accounts_token' => $this->getAccountsDataProvider()->getAccountsToken(),
                'accounts_shop_id' => $this->getAccountsDataProvider()->getAccountsShopId(),
            ]);
            $distributionApi->setBearer($this->getAdminAuthenticationProvider()->getMboJWT());
            $distributionApi->{$method}($params);

            if (file_exists($lockFile)) {
                unlink($lockFile);
            }
        } catch (Exception $exception) {
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
        $this->configurationList['PS_MBO_LAST_PS_VERSION_API_CONFIG'] = _PS_VERSION_;

        foreach (Shop::getShops(false, null, true) as $shopId) {
            foreach ($this->configurationList as $name => $value) {
                if (false === Configuration::hasKey($name, null, null, (int) $shopId)) {
                    $result = $result && (bool) Configuration::updateValue(
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

    private function syncApiConfig()
    {
        if (file_exists($this->moduleCacheDir . 'registerShop.lock')) {
            // The shop is not registered yet, do nothing
            return;
        }

        /** @var Client $distributionApi */
        $distributionApi = $this->getService('mbo.cdc.client.distribution_api');

        $distributionApi->setBearer($this->getAdminAuthenticationProvider()->getMboJWT());
        $config = $distributionApi->getApiConf();

        if (empty($config)) {
            return;
        }

        // We need that conversion to ensure we have an array instead of stdClass
        $config = json_decode(json_encode($config), true);

        $command = new ConfigChangeCommand($config, _PS_VERSION_, $this->version);
        $configCollection = $this->getService('mbo.distribution.api_config_change_handler')->handle($command);
    }
}
