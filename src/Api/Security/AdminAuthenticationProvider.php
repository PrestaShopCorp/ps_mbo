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

namespace PrestaShop\Module\Mbo\Api\Security;

use Doctrine\Common\Cache\CacheProvider;
use Firebase\JWT\JWT;
use PrestaShop\Module\Mbo\Helpers\Config;
use PrestaShop\PrestaShop\Core\Domain\Employee\Exception\EmployeeException;
use PrestaShop\PrestaShop\Core\Exception\CoreException;

class AdminAuthenticationProvider
{
    private const DEFAULT_EMPLOYEE_ID = 42;

    /**
     * @var CacheProvider
     */
    private $cacheProvider;

    public function __construct(
        CacheProvider $cacheProvider,
    ) {
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * @param \Employee $apiUser
     *
     * @return \Cookie
     *
     * @throws CoreException
     */
    public function apiUserLogin(\Employee $apiUser): \Cookie
    {
        $cookie = new \Cookie('apiPsMbo');
        $cookie->id_employee = (int) $apiUser->id;
        // @phpstan-ignore-next-line
        $cookie->email = $apiUser->email;
        // @phpstan-ignore-next-line
        $cookie->profile = $apiUser->id_profile;
        $cookie->passwd = $apiUser->passwd;
        // @phpstan-ignore-next-line
        $cookie->remote_addr = $apiUser->remote_addr;
        $cookie->registerSession(new \EmployeeSession());

        if (!\Tools::getValue('stay_logged_in')) {
            $cookie->last_activity = time();
        }

        $cookie->write();

        return $cookie;
    }

    /**
     * @throws EmployeeException
     * @throws \Doctrine\DBAL\Exception
     */
    public function getAdminToken(): string
    {
        $cacheKey = $this->getAdminTokenCacheKey();

        if (!($token = $this->cacheProvider->fetch($cacheKey))) {
            $token = $this->getDefaultUserToken();
        }

        return $token;
    }

    /**
     * @throws EmployeeException
     * @throws \Doctrine\DBAL\Exception
     */
    public function getMboJWT(): string
    {
        $cacheKey = $this->getJwtTokenCacheKey();

        if ($this->cacheProvider->contains($cacheKey)) {
            return $this->cacheProvider->fetch($cacheKey);
        }

        $mboUserToken = $this->getAdminToken();

        $shopUrl = Config::getShopUrl();
        $shopUuid = Config::getShopMboUuid();

        $jwtToken = JWT::encode(['shop_url' => $shopUrl, 'shop_uuid' => $shopUuid], $mboUserToken, 'HS256');

        // Don't put in cache if we have the default user token
        if ($this->getDefaultUserToken() === $mboUserToken) {
            return $jwtToken;
        }

        // Lifetime infinite, will be purged when MBO is uninstalled
        $this->cacheProvider->save($cacheKey, $jwtToken, 0);

        return $this->cacheProvider->fetch($cacheKey);
    }

    public function clearCache(): void
    {
        $this->cacheProvider->delete($this->getAdminTokenCacheKey());
        $this->cacheProvider->delete($this->getJwtTokenCacheKey());
    }

    private function getAdminTokenCacheKey(): string
    {
        return sprintf('mbo_admin_token_%s', Config::getShopMboUuid());
    }

    private function getJwtTokenCacheKey(): string
    {
        return sprintf('mbo_jwt_token_%s', Config::getShopMboUuid());
    }

    private function getDefaultUserToken(): string
    {
        $idTab = \Tab::getIdFromClassName('apiPsMbo');

        return \Tools::getAdminToken('apiPsMbo' . (int) $idTab . self::DEFAULT_EMPLOYEE_ID);
    }
}
