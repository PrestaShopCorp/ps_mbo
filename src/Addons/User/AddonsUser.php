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

namespace PrestaShop\Module\Mbo\Addons\User;

use PrestaShop\Module\Mbo\Accounts\Provider\AccountsDataProvider;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * This class will read user information stored in cookies
 */
class AddonsUser implements UserInterface
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var AccountsDataProvider
     */
    private $accountsDataProvider;

    public function __construct(
        SessionInterface $session,
        AccountsDataProvider $accountsDataProvider
    ) {
        $this->session = $session;
        $this->accountsDataProvider = $accountsDataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthenticated(): bool
    {
        return $this->hasAccountsTokenInSession() || $this->isConnectedOnPsAccounts();
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(bool $encrypted = false): ?array
    {
        $accountsToken = $this->getAccountsTokenFromSession();
        if (null !== $accountsToken) {
            return ['accounts_token' => (string) $accountsToken];
        }

        // accounts
        $accountsToken = $this->accountsDataProvider->getAccountsToken();
        if (!empty($accountsToken)) {
            return ['accounts_token' => (string) $accountsToken];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmail(): array
    {
        $email = null;
        if ($this->isAuthenticated()) {
            // Connected on ps_accounts
            if ($this->isConnectedOnPsAccounts()) {
                $email = $this->accountsDataProvider->getAccountsUserEmail();
            } elseif ($this->hasAccountsTokenInSession()) { // Connected on ps_accounts with session
                $email = $this->jwtDecode($this->getAccountsTokenFromSession())['email'];
            }
        }

        return [
            'username' => $email,
        ];
    }

    public function hasAccountsTokenInSession(): bool
    {
        return null !== $this->getAccountsTokenFromSession();
    }

    public function isConnectedOnPsAccounts(): bool
    {
        $accountsToken = $this->accountsDataProvider->getAccountsToken();

        return !empty($accountsToken);
    }

    public function getAccountsShopUuid(): ?string
    {
        return $this->accountsDataProvider->getAccountsShopId();
    }

    /**
     * @return mixed
     */
    private function getAccountsTokenFromSession()
    {
        return $this->session->get('accounts_token');
    }

    /**
     * {@inheritdoc}
     */
    private function jwtDecode(string $token): array
    {
        $payload = explode('.', $token)[1];
        $jsonToken = base64_decode($payload);

        return json_decode($jsonToken, true);
    }
}
