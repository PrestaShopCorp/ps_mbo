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

/**
 * This class will provide data from Addons API
 */
class CookieAddonsUser implements AddonsUserInterface
{
    /**
     * @var PhpEncryption
     */
    protected $encryption;
    /**
     * @var mixed
     */
    protected $username;
    /**
     * @var mixed
     */
    protected $password;

    public function __construct(?string $username, ?string $password)
    {
        $this->encryption = new PhpEncryption(_NEW_COOKIE_KEY_);
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @param string $key
     *
     * @return bool|string
     *
     * @throws \Exception
     */
    protected function decrypt(string $key)
    {
        return $this->encryption->decrypt($this->{$key});
    }

    /**
     * {@inheritdoc}
     */
    public function isAddonsAuthenticated(): bool
    {
        return $this->username && $this->password;
    }

    /**
     * {@inheritdoc}
     */
    public function getAddonsCredentials(): array
    {
        return [
            'username_addons' => $this->decrypt('username'),
            'password_addons' => $this->decrypt('password'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAddonsEmail(): array
    {
        return [
            'username_addons' => $this->decrypt('username'),
        ];
    }
}
