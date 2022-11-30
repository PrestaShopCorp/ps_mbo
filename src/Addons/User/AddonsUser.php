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

    public function __construct(SessionInterface $session, CredentialsEncryptor $encryption)
    {
        $this->encryption = $encryption;
        $this->request = Request::createFromGlobals();
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthenticated(): bool
    {
        return $this->hasCookieAuthenticated() || $this->hasSessionAuthenticated();
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(bool $encrypted = false): array
    {
        return $encrypted ?
            [
                'username' => $this->get('username_addons'),
                'password' => $this->get('password_addons'),
            ]
            : [
            'username' => $this->getAndDecrypt('username_addons'),
            'password' => $this->getAndDecrypt('password_addons'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getEmail(): array
    {
        return [
            'username' => $this->getAndDecrypt('username_addons'),
        ];
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
        return $this->getFromCookie('username_addons')
            && $this->getFromCookie('password_addons');
    }

    /**
     * {@inheritdoc}
     */
    private function hasSessionAuthenticated(): bool
    {
        return $this->getFromSession('username_addons')
            && $this->getFromSession('password_addons');
    }
}
