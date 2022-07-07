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

use DateTime;
use Doctrine\DBAL\Connection;
use LogicException;
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

    public function __construct(
        Connection $connection,
        string $dbPrefix
    ) {
        $this->connection = $connection;
        $this->dbPrefix = $dbPrefix;
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
}
