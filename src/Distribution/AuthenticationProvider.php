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

namespace PrestaShop\Module\Mbo\Distribution;

use Doctrine\Common\Cache\CacheProvider;
use Firebase\JWT\JWT;
use PrestaShop\Module\Mbo\Helpers\Config;

class AuthenticationProvider
{
    /**
     * @var CacheProvider
     */
    private $cacheProvider;

    public function __construct(
        CacheProvider $cacheProvider
    ) {
        $this->cacheProvider = $cacheProvider;
    }

    public function getMboJWT()
    {
        $cacheKey = $this->getJwtTokenCacheKey();

        if ($this->cacheProvider->contains($cacheKey)) {
            return $this->cacheProvider->fetch($cacheKey);
        }

        $shopUrl = Config::getShopUrl();
        $shopUuid = Config::getShopMboUuid();

        $jwtToken = JWT::encode(['shop_url' => $shopUrl, 'shop_uuid' => $shopUuid], md5($shopUuid), 'HS256');

        $this->cacheProvider->save($cacheKey, $jwtToken, 0); // Lifetime infinite, will be purged when MBO is uninstalled

        return $this->cacheProvider->fetch($cacheKey);
    }

    public function clearCache()
    {
        $this->cacheProvider->delete($this->getJwtTokenCacheKey());
    }

    private function getJwtTokenCacheKey()
    {
        return sprintf('mbo_jwt_token_%s', Config::getShopMboUuid());
    }
}
