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

use Exception;
use PrestaShop\Module\Mbo\Accounts\Provider\AccountsDataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * This class will read user information stored in cookies
 */
class AddonsUser implements UserInterface
{
    /**
     * @var CredentialsEncryptor
     */
    protected $encryption;

    /**
     * @var Request
     */
    private $request;

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
        CredentialsEncryptor $encryption,
        AccountsDataProvider $accountsDataProvider
    ) {
        $this->encryption = $encryption;
        $this->request = Request::createFromGlobals();
        $this->session = $session;
        $this->accountsDataProvider = $accountsDataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthenticated(): bool
    {
        return $this->hasAccountsTokenInSession() || $this->isConnectedOnPsAccounts() || $this->hasCookieAuthenticated() || $this->hasSessionAuthenticated();
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(bool $encrypted = false): array
    {
        $accountsToken = $this->getFromSession('accounts_token');

        if (null !== $accountsToken) {
            return ['accounts_token' => (string) $accountsToken];
        }

        // accounts
        $accountsToken = $this->accountsDataProvider->getAccountsToken();
        if (!empty($accountsToken)) {
            return ['accounts_token' => (string) $accountsToken];
        }

        return $encrypted ?
            [
                'username' => $this->get('username_addons_v2'),
                'password' => $this->get('password_addons_v2'),
            ]
            : [
                'username' => $this->getAndDecrypt('username_addons_v2'),
                'password' => $this->getAndDecrypt('password_addons_v2'),
            ];
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
                $email = $this->jwtDecode($this->getFromSession('accounts_token'))['email'];
            } else { // Connected on addons
                $email = $this->getAndDecrypt('username_addons_v2');
            }
        }

        return [
            'username' => $email,
        ];
    }

    public function hasAccountsTokenInSession(): bool
    {
        return null !== $this->getFromSession('accounts_token');
    }

    public function isConnectedOnPsAccounts(): bool
    {
        $accountsToken = $this->accountsDataProvider->getAccountsToken();

        return !empty($accountsToken);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    private function getFromCookie(string $key)
    {
        return $this->request->cookies->get($key);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    private function getFromSession(string $key)
    {
        return $this->session->get($key);
    }

    /**
     * @param string $key
     *
     * @return string|null
     *
     * @throws Exception
     */
    private function getAndDecrypt(string $key): ?string
    {
        $value = $this->get($key);
        if (null !== $value) {
            return $this->encryption->decrypt($value);
        }

        return null;
    }

    /**
     * @param string $key
     *
     * @return string|null
     *
     * @throws Exception
     */
    private function get(string $key): ?string
    {
        $sessionValue = $this->getFromSession($key);
        if (null !== $sessionValue) {
            return $sessionValue;
        }

        return $this->getFromCookie($key);
    }

    /**
     * {@inheritdoc}
     */
    private function hasCookieAuthenticated(): bool
    {
        return $this->getFromCookie('username_addons_v2')
            && $this->getFromCookie('password_addons_v2');
    }

    /**
     * {@inheritdoc}
     */
    private function hasSessionAuthenticated(): bool
    {
        return $this->getFromSession('username_addons_v2')
            && $this->getFromSession('password_addons_v2');
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
