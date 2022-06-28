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

namespace PrestaShop\Module\Mbo\Api\Service;

use DateTime;
use Doctrine\DBAL\Connection;
use LogicException;
use Tools;

class ApiAuthorizationService
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

    /**
     * This method will authorize the call.
     * For now, it'll always return true because the token validation is done by the PS Admin itself.
     * We use it to extend the token validity.
     */
    public function authorizeCall(): bool
    {
        $this->extendTokenValidity();

        return true;
    }

    public function updateConsent(bool $newConsentValue): bool
    {
        $consentParams = [
            'consent' => $newConsentValue,
        ];

        if (true === $newConsentValue) {
            // Token of the user who updated the value.
            // By default, this token will be used to perform API calls.
            // Maybe we'll need to generate a dedicated token.
            $consentParams['token'] = Tools::getValue('token');
            $consentParams['admin_dir'] = trim(str_replace(_PS_ROOT_DIR_, '', _PS_ADMIN_DIR_), '/');
        }

        // Here we need a call to the NEST API client to transmit consent values

        return true;
    }

    private function extendTokenValidity(): void
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
