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

namespace PrestaShop\Module\Mbo;

use Configuration;
use Db;
use Module;
use PrestaShopLogger;
use Psr\Log\LoggerInterface;
use Shop;
use Symfony\Component\Dotenv\Dotenv;

class UpgradeTracker
{
    /**
     * @param Module $module
     *
     * @return bool
     */
    public function postTracking(Module $module, string $nextVersion = null)
    {
        try {
            // Load env variables
            $dotenv = new Dotenv();
            $dotenv->load(__DIR__ . '/../.env');

            $apiBaseUrl = getenv('DISTRIBUTION_API_URL');

            if (is_string($apiBaseUrl) && !empty($apiBaseUrl)) {
                // Execute the API call
                $this->sendRequest(
                    $apiBaseUrl . '/api/shops/track-upgrade-mbo',
                    [
                        'shop_url' => $this->getShopUrl(),
                        'ps_version' => _PS_VERSION_,
                        // At this point, the module is not changed in DB yet but module->database_version is null. So we make  DB query
                        'from_mbo_version' => $this->getCurrentModuleVersion(),
                        'to_mbo_version' => $nextVersion ?: $this->getNextModuleVersion(),
                    ]
                );
            }
        } catch (\Exception $e) {
            $message = 'Upgrade tracking on Distribution failed : ' . $e->getMessage();
            $logger = $module->get('logger');
            if ($logger instanceof LoggerInterface) {
                $logger->warning($message);
            }
            PrestaShopLogger::addLog($message, 2);
        }

        return true;
    }

    /**
     * @param string $url
     * @param array $data
     */
    private function sendRequest($url, $data)
    {
        if (function_exists('curl_init') && defined('CURLOPT_POST')) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
            ]);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
            $content = curl_exec($curl);
            $httpcode = curl_getinfo($curl);

            $error = curl_error($curl);
            if ($error) {
                $errno = curl_errno($curl);
                curl_close($curl);

                throw new \Exception(sprintf('Response code : %s. Error : %s', (string) $httpcode['http_code'], $error), $errno);
            }

            curl_close($curl);
        }
    }

    /**
     * The Tools::usingSecureMode used by Shop::getBaseUrl seems to not work in all situations
     * To be sure to have the correct data, use shop configuration
     *
     * @return string|null
     */
    private function getShopUrl()
    {
        $shopUrl = null;
        $singleShop = $this->getSingleShop();
        $useSecureProtocol = $this->isUsingSecureProtocol();
        $domainConfigKey = $useSecureProtocol ? 'PS_SHOP_DOMAIN_SSL' : 'PS_SHOP_DOMAIN';

        $domain = Configuration::get(
            $domainConfigKey,
            null,
            $singleShop->id_shop_group,
            $singleShop->id
        );

        if ($domain) {
            $domain = preg_replace('#(https?://)#', '', $domain);
            $shopUrl = ($useSecureProtocol ? 'https://' : 'http://') . $domain;
        }

        return $shopUrl;
    }

    /**
     * @return bool
     */
    private function isUsingSecureProtocol()
    {
        $singleShop = $this->getSingleShop();

        return (bool) Configuration::get(
            'PS_SSL_ENABLED',
            null,
            $singleShop->id_shop_group,
            $singleShop->id
        );
    }

    /**
     * @return Shop
     */
    private function getSingleShop()
    {
        $shops = Shop::getShops(false, null, true);

        return new Shop((int) reset($shops));
    }

    /**
     * @return string
     */
    private function getCurrentModuleVersion()
    {
        return (string) Db::getInstance()->getValue(sprintf("SELECT `version` FROM `%smodule` WHERE `name`='ps_mbo'", _DB_PREFIX_));
    }

    /**
     * Because there is an issue after upgrade : the version in module->version is the old one.
     *
     * @return string
     *
     * @throws \Exception
     */
    private function getNextModuleVersion()
    {
        $moduleMainFile = sprintf('%s/ps_mbo/ps_mbo.php', rtrim(_PS_MODULE_DIR_, '/'));
        if (!file_exists($moduleMainFile)) {
            throw new \Exception(sprintf('Could not find module main file at %s', $moduleMainFile));
        }

        $moduleClassContent = file_get_contents($moduleMainFile);
        preg_match('/const[ ]+VERSION[ ]*=[ ]*\'(.+)\'/', $moduleClassContent, $matches);

        if (!empty($matches)) {
            $version = $matches[1];
        } else {
            preg_match('/this->version[ ]*=[ ]*\'(.+)\'/', $moduleClassContent, $matches);

            if (!empty($matches)) {
                $version = $matches[1];
            }
        }

        if (empty($version)) {
            throw new \Exception(sprintf('Could not find module version in the main file at %s', $moduleMainFile));
        }

        return (string) $version;
    }
}
