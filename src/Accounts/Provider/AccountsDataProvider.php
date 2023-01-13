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

namespace PrestaShop\Module\Mbo\Accounts\Provider;

use Exception;
use PrestaShop\Module\PsAccounts\Repository\UserTokenRepository;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\PrestaShop\Adapter\ServiceLocator;
use PrestaShop\PsAccountsInstaller\Installer\Facade\PsAccounts;

class AccountsDataProvider
{
    /**
     * @var PsAccounts
     */
    private $psAccountsFacade;

    public function __construct(PsAccounts $psAccountsFacade)
    {
        $this->psAccountsFacade = $psAccountsFacade;
    }

    public function getAccountsToken(): string
    {
        if (!$this->isAccountLinked()) {
            return '';
        }

        $psAccountsModule = ServiceLocator::get('ps_accounts');

        if (null === $psAccountsModule) {
            return '';
        }

        /**
         * @var UserTokenRepository $accountsUserTokenRepository
         */
        $accountsUserTokenRepository = $psAccountsModule->getService(UserTokenRepository::class);
        try {
            $token = $accountsUserTokenRepository->getOrRefreshToken();
        } catch (Exception $e) {
            return '';
        }

        return null === $token ? '' : (string) $token;
    }

    public function getAccountsShopId(): ?string
    {
        if (!$this->isAccountLinked()) {
            return null;
        }

        return $this->getAccountsService()->getShopUuid() ?? null;
    }

    public function getAccountsUserId(): ?string
    {
        try {
            $userUuid = $this->getAccountsService()->getUserUuid();
        } catch (Exception $e) {
            $userUuid = null;
        }

        return $userUuid ? $userUuid : null;
    }

    public function getAccountsUserEmail(): ?string
    {
        try {
            $email = $this->getAccountsService()->getEmail();
        } catch (Exception $e) {
            $email = null;
        }

        return $email;
    }

    private function isAccountLinked(): bool
    {
        try {
            return $this->getAccountsService()->isAccountLinked();
        } catch (Exception $e) {
            return false;
        }
    }

    private function getAccountsService(): PsAccountsService
    {
        return $this->psAccountsFacade->getPsAccountsService();
    }
}
