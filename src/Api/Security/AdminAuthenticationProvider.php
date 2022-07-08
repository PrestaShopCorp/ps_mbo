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

use Configuration;
use Cookie;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Employee;
use EmployeeSession;
use LogicException;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShop\PrestaShop\Core\Crypto\Hashing;
use PrestaShop\PrestaShop\Core\Domain\Employee\Exception\EmployeeException;
use PrestaShop\PrestaShop\Core\Exception\CoreException;
use PrestaShopDatabaseException;
use PrestaShopException;
use Tools;

class AdminAuthenticationProvider
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $dbPrefix;

    /**
     * @var Hashing
     */
    private $hashing;

    /**
     * @var LegacyContext
     */
    private $context;

    public function __construct(
        Connection $connection,
        string $dbPrefix,
        Hashing $hashing,
        LegacyContext $context
    ) {
        $this->connection = $connection;
        $this->dbPrefix = $dbPrefix;
        $this->hashing = $hashing;
        $this->context = $context;
    }

    /**
     * @return Employee
     *
     * @throws EmployeeException
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function createApiUser(): Employee
    {
        $employee = $this->getApiUser();

        if (null !== $employee) {
            return $employee;
        }

        $employee = new Employee();
        $employee->firstname = 'Prestashop';
        $employee->lastname = 'Marketplace';
        $employee->email = Configuration::get('PS_MBO_SHOP_ADMIN_MAIL');
        $employee->id_lang = $this->context->getContext()->language->id;
        $employee->id_profile = _PS_ADMIN_PROFILE_;
        $employee->active = true;
        $employee->passwd = $this->hashing->hash(uniqid('', true));

        if (!$employee->add()) {
            throw new EmployeeException('Failed to add PsMBO API user');
        }

        return $employee;
    }

    /**
     * @throws PrestaShopException
     */
    public function deleteApiUser()
    {
        $employee = $this->getApiUser();

        if (null !== $employee) {
            $employee->delete();
        }
    }

    /**
     * @return Employee
     */
    public function ensureApiUserExistence(): Employee
    {
        $apiUser = $this->getApiUser();

        if (null === $apiUser) {
            $apiUser = $this->createApiUser();
        }

        return $apiUser;
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
        $cookie->email = $apiUser->email;
        $cookie->profile = $apiUser->id_profile;
        $cookie->passwd = $apiUser->passwd;
        $cookie->remote_addr = $apiUser->remote_addr;
        $cookie->registerSession(new EmployeeSession());

        if (!Tools::getValue('stay_logged_in')) {
            $cookie->last_activity = time();
        }

        $cookie->write();

        return $cookie;
    }

    public function extendTokenValidity(): void
    {
        try {
            $token = Tools::getValue('token');

            if (empty($token)) {
                throw new LogicException('Token was not supposed to be empty here');
            }

            $qb = $this->connection->createQueryBuilder();
            $qb->update($this->dbPrefix . 'employee_session')
                ->set('date_upd', (new DateTime())->format('Y-m-d H:i:s'));

            $qb->execute();
        } catch (\Exception $e) {
            // Exception will remain silent because the call cannot be blocked when this task does not go well.
            // Maybe add a log here
        }
    }

    /**
     * @return Employee|null
     *
     * @throws Exception
     */
    private function getApiUser(): ?Employee
    {
        //Get employee ID
        $qb = $this->connection->createQueryBuilder();
        $qb->select('e.id_employee')
            ->from($this->dbPrefix . 'employee', 'e')
            ->andWhere('e.email = :email')
            ->andWhere('e.active = :active')
            ->setParameter('email', Configuration::get('PS_MBO_SHOP_ADMIN_MAIL'))
            ->setParameter('active', true)
            ->setMaxResults(1);

        $employees = $qb->execute()->fetchAll();

        if (empty($employees)) {
            return null;
        }

        return new Employee((int) $employees[0]['id_employee']);
    }
}
