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
use PrestaShop\Module\Mbo\Api\Exception\UnauthorizedException;
use PrestaShop\Module\Mbo\Helpers\Config;
use PrestaShop\PrestaShop\Core\Context\ApiClientContext;
use PrestaShop\PrestaShop\Core\Context\EmployeeContext;
use PrestaShop\PrestaShop\Core\Domain\Employee\Exception\EmployeeException;
use PrestaShop\PrestaShop\Core\Exception\CoreException;

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminAuthenticationProvider
{
    public function __construct(
        private readonly EmployeeContext $employeeContext,
        private readonly ApiClientContext $apiClientContext,
        private readonly CacheProvider $cacheProvider,
    ) {
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
    public function getMboJWT(): string
    {
        $shopUrl = Config::getShopUrl();
        $cacheKey = $this->getJwtTokenCacheKey();

        if (!($jwtToken = $this->cacheProvider->fetch($cacheKey))) {
            $mboToken = $this->getMboToken();
            $jwtToken = JWT::encode([
                'shop_url' => $shopUrl,
                'mbo_version' => \ps_mbo::VERSION,
                'ps_version' => _PS_VERSION_,
            ], $mboToken, 'HS256');

            // Lifetime infinite, will be purged when MBO is uninstalled
            $this->cacheProvider->save($cacheKey, $jwtToken, 0);
        }

        return $jwtToken;
    }

    public function clearCache(): void
    {
        $this->cacheProvider->delete($this->getJwtTokenCacheKey());
    }

    private function getMboToken(): string
    {
        if ($this->employeeContext->getEmployee()) {
            $salt = $this->employeeContext->getEmployee()->getId();
        } elseif ($this->apiClientContext->getApiClient()) {
            $salt = $this->apiClientContext->getApiClient()->getId();
        } else {
            throw new UnauthorizedException('No employee or api client found');
        }

        return \Tools::getAdminToken('apiPsMbo' . \Tab::getIdFromClassName('apiPsMbo') . $salt);
    }

    private function getJwtTokenCacheKey(): string
    {
        return sprintf('mbo_jwt_token_%s', $this->getMboToken());
    }
}
