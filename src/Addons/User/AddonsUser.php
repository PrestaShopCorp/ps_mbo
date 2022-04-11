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
use PhpEncryptionCore as PhpEncryption;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * This class will read user information stored in cookies
 */
class AddonsUser implements UserInterface
{
    /**
     * @var PhpEncryption
     */
    private $encryption;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->encryption = new PhpEncryption(_NEW_COOKIE_KEY_);
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
    public function getCredentials(): array
    {
        return [
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
        $sessionValue = $this->getFromSession($key);
        if (null !== $sessionValue) {
            return $this->encryption->decrypt($sessionValue);
        }

        $cookieValue = $this->getFromCookie($key);
        if (null !== $cookieValue) {
            return $this->encryption->decrypt($cookieValue);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    private function hasCookieAuthenticated(): bool
    {
        return $this->getFromCookie('username_addons', false)
            && $this->getFromCookie('password_addons', false);
    }

    /**
     * {@inheritdoc}
     */
    private function hasSessionAuthenticated(): bool
    {
        return $this->getFromSession('username_addons', false)
            && $this->getFromSession('password_addons', false);
    }
}
