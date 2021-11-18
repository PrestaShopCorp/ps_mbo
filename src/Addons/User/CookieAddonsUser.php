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

use PhpEncryptionCore as PhpEncryption;
use Symfony\Component\HttpFoundation\Request;

/**
 * This class will provide data from Addons API
 */
class CookieAddonsUser implements AddonsUserInterface
{
    /**
     * @var PhpEncryption
     */
    private $encryption;

    /**
     * @var Request
     */
    private $request;

    public function __construct()
    {
        $this->encryption = new PhpEncryption(_NEW_COOKIE_KEY_);
        $this->request = Request::createFromGlobals();
    }

    /**
     * @return static
     */
    public static function buildAddonsUser(): self
    {
        $user = new self();

        return $user->setRequest(Request::createFromGlobals());
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return static
     */
    public function setRequest(Request $request): self
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    private function getFromCookie(string $key, $default = null)
    {
        return $this->request->cookies->get($key, $default);
    }

    /**
     * @param string $key
     * @param null $default
     *
     * @return bool|string
     *
     * @throws \Exception
     */
    private function getAndDecrypt(string $key, $default = null)
    {
        return $this->encryption->decrypt($this->getFromCookie($key, $default));
    }

    /**
     * {@inheritdoc}
     */
    public function isAddonsAuthenticated(): bool
    {
        return $this->getFromCookie('username_addons', false)
            && $this->getFromCookie('password_addons', false);
    }

    /**
     * {@inheritdoc}
     */
    public function getAddonsCredentials(): array
    {
        return [
            'username_addons' => $this->getAndDecrypt('username_addons'),
            'password_addons' => $this->getAndDecrypt('password_addons'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAddonsEmail(): array
    {
        return [
            'username_addons' => $this->getAndDecrypt('username_addons'),
        ];
    }
}
