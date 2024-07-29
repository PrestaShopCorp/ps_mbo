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

namespace PrestaShop\Module\Mbo\Addons\User;

use Exception;
use Symfony\Component\HttpFoundation\Request;

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

    public function __construct(
        CredentialsEncryptor $encryption
    ) {
        $this->encryption = $encryption;
        $this->request = Request::createFromGlobals();
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthenticated()
    {
        return $this->hasCookieAuthenticated();
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials($encrypted = false)
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
    public function getEmail()
    {
        $email = null;
        if ($this->isAuthenticated()) {
            $email = $this->getAndDecrypt('username_addons');
        }

        return [
            'username' => $email,
        ];
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    private function getFromCookie($key)
    {
        return $this->request->cookies->get($key);
    }

    /**
     * @param string $key
     *
     * @return string|null
     *
     * @throws Exception
     */
    private function getAndDecrypt($key)
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
    private function get($key)
    {
        return $this->getFromCookie($key);
    }

    /**
     * {@inheritdoc}
     */
    private function hasCookieAuthenticated()
    {
        return $this->getFromCookie('username_addons')
            && $this->getFromCookie('password_addons');
    }
}
