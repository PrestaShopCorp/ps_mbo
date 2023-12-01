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

namespace PrestaShop\Module\Mbo\Service;

use PrestaShop\PrestaShop\Core\Cache\Clearer\CacheClearerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class MboSymfonyCacheClearer implements CacheClearerInterface
{

    private $shutdownRegistered = false;

    /**
     * @inheritDoc
     */
    public function clear()
    {
        if ($this->shutdownRegistered) {
            return;
        }

        $this->shutdownRegistered = true;
        register_shutdown_function(function () {
            // The cache may have been removed by Tools::clearSf2Cache, it happens during install
            // process, in which case we don't run the cache:clear command because it is not only
            // useless it will simply fail as the container caches classes have been removed
            $cacheDir = _PS_ROOT_DIR_ . '/var/cache/' . _PS_ENV_ . '/';
            if (file_exists($cacheDir)) {
                $cache_files = Finder::create()
                    ->in($cacheDir)
                    ->depth('==0');
                (new Filesystem())->remove($cache_files);
            }

            \Hook::exec('actionClearSf2Cache');
        });
    }
}
