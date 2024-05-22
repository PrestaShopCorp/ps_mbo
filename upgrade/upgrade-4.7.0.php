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

$rootDir = defined('_PS_ROOT_DIR_') ? _PS_ROOT_DIR_ : getenv('_PS_ROOT_DIR_');
if (!$rootDir) {
    $rootDir = __DIR__ . '/../../../';
}

require_once $rootDir . '/vendor/autoload.php';

/**
 * @param ps_mbo $module
 *
 * @return bool
 */
function upgrade_module_4_7_0(Module $module): bool
{
    $module->updateHooks();
    $module->updateTabs();

    $singleShop = \PrestaShop\Module\Mbo\Helpers\Config::getDefaultShop();
    $domains = \Tools::getDomains();

    $shopDomain = array_filter(
        $domains,
        function($domain) use($singleShop) {
            // Here we assume that every shop have a single domain (?)
            $domain = reset($domain);
            return isset($domain['id_shop']) && (int)$singleShop->id === (int)$domain['id_shop'];
        }
    );

    if (!empty($shopDomain)) {
        $domain = array_keys($shopDomain)[0];
        $domain = preg_replace('#(https?://)#', '', $domain);

        // concatenate the physical_uri
        $domainDef = reset($shopDomain[$domain]);
        if (isset($domainDef['physical']) && '/' !== $domainDef['physical']) {
            $moduleCacheDir = $module->moduleCacheDir;
            $lockFile = $moduleCacheDir . 'updateShop.lock';
            if (!file_exists($lockFile)) {
                if (!is_dir($moduleCacheDir)) {
                    mkdir($moduleCacheDir, 0777, true);
                }
                $f = fopen($lockFile, 'w+');
                fclose($f);
            }
        }
    }

    return true;
}
