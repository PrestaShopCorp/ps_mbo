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

use Context;
use Cookie;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\DBAL\Connection;
use Employee;
use EmployeeSession;
use Exception;
use Firebase\JWT\JWT;
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;
use PrestaShop\Module\Mbo\Helpers\Config;
use PrestaShop\PrestaShop\Core\Crypto\Hashing;
use PrestaShop\PrestaShop\Core\Domain\Employee\Exception\EmployeeException;
use PrestaShop\PrestaShop\Core\Exception\CoreException;
use Shop;
use Tab;
use Tools;

class AdminAuthenticationProvider
{
    private const DEFAULT_EMPLOYEE_ID = 42;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $dbPrefix;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var Hashing
     */
    private $hashing;

    /**
     * @var CacheProvider
     */
    private $cacheProvider;

    public function __construct(
        Connection $connection,
        Context $context,
        Hashing $hashing,
        CacheProvider $cacheProvider,
        string $dbPrefix
    ) {
        $this->connection = $connection;
        $this->dbPrefix = $dbPrefix;
        $this->context = $context;
        $this->hashing = $hashing;
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function createApiUser(): ?Employee
    {
        $moduleCacheDir = sprintf('%s/var/modules/ps_mbo/', rtrim(_PS_ROOT_DIR_, '/'));
        $lockFile = $moduleCacheDir . 'createApiUser.lock';

        $employee = $this->getApiUser();

        if (null !== $employee) {
            if (file_exists($lockFile)) {
                unlink($lockFile);
            }

            return $employee;
        }

        $this->deletePossibleApiUser();

        $employee = new Employee();
        $employee->firstname = 'Prestashop';
        $employee->lastname = 'Marketplace';
        $employee->email = Config::getShopMboAdminMail();
        $employee->id_lang = $this->context->language->id;
        $employee->id_profile = _PS_ADMIN_PROFILE_;
        $employee->active = true;
        $employee->passwd = $this->hashing->hash(uniqid('', true));

        try {
            if (!$employee->add()) {
                throw new EmployeeException('Failed to add PsMBO API user');
            }

            if (file_exists($lockFile)) {
                unlink($lockFile);
            }
        } catch (Exception $e) {
            // Create the lock file
            if (!file_exists($lockFile)) {
                if (!is_dir($moduleCacheDir)) {
                    mkdir($moduleCacheDir, 0777, true);
                }
                $f = fopen($lockFile, 'w+');
                fclose($f);
            }

            $this->logFailedEmployeeException($e);

            return null;
        }

        return $employee;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function getApiUser(): ?Employee
    {
        //Get employee ID
        $qb = $this->connection->createQueryBuilder();
        $qb->select('e.id_employee')
            ->from($this->dbPrefix . 'employee', 'e')
            ->andWhere('e.email = :email')
            ->andWhere('e.active = :active')
            ->setParameter('email', Config::getShopMboAdminMail())
            ->setParameter('active', true)
            ->setMaxResults(1);

        $employees = $qb->execute()->fetchAll();

        if (empty($employees)) {
            return null;
        }

        return new Employee((int) $employees[0]['id_employee']);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function ensureApiUserExistence(): ?Employee
    {
        $apiUser = $this->getApiUser();

        if (null === $apiUser) {
            $apiUser = $this->createApiUser();
        }

        return $apiUser;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function deletePossibleApiUser(): void
    {
        $connection = $this->connection;

        $qb = $connection->createQueryBuilder();
        $qb->delete($this->dbPrefix . 'employee')
            ->where("email LIKE 'mbo-%prestashop.com'");

        $qb->execute();
    }

    /**
     * @param Employee $apiUser
     *
     * @return Cookie
     *
     * @throws CoreException
     */
    public function apiUserLogin(Employee $apiUser): Cookie
    {
        $cookie = new Cookie('apiPsMbo');
        $cookie->id_employee = (int) $apiUser->id;
        // @phpstan-ignore-next-line
        $cookie->email = $apiUser->email;
        // @phpstan-ignore-next-line
        $cookie->profile = $apiUser->id_profile;
        $cookie->passwd = $apiUser->passwd;
        // @phpstan-ignore-next-line
        $cookie->remote_addr = $apiUser->remote_addr;
        $cookie->registerSession(new EmployeeSession());

        if (!Tools::getValue('stay_logged_in')) {
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

        if ($this->cacheProvider->contains($cacheKey)) {
            return $this->cacheProvider->fetch($cacheKey);
        }

        $apiUser = $this->ensureApiUserExistence();
        $idTab = Tab::getIdFromClassName('apiPsMbo');

        // An error on user creation, use a default user (?) and don't cache it
        if (!$apiUser) {
            return $this->getDefaultUserToken();
        }

        $token = Tools::getAdminToken('apiPsMbo' . (int) $idTab . (int) $apiUser->id);

        $this->cacheProvider->save($cacheKey, $token, 0); // Lifetime infinite, will be purged when MBO is uninstalled

        return $this->cacheProvider->fetch($cacheKey);
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
        $idTab = Tab::getIdFromClassName('apiPsMbo');

        return Tools::getAdminToken('apiPsMbo' . (int) $idTab . self::DEFAULT_EMPLOYEE_ID);
    }

    private function logFailedEmployeeException(Exception $e): void
    {
        ErrorHelper::reportError($e, [
            'shop_mbo_uuid' => Config::getShopMboUuid(),
            'shop_mbo_admin_mail' => Config::getShopMboAdminMail(),
            'shop_url' => Config::getShopUrl(),
            'multishop' => Shop::isFeatureActive(),
            'number_of_shops' => Shop::getTotalShops(false, null),
        ]);
    }
}
