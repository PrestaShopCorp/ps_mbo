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

use PrestaShop\Module\Mbo\Api\Security\AdminAuthenticationProvider;

class CredentialsEncryptor
{
    /**
     * @var AdminAuthenticationProvider
     */
    private $adminAuthenticationProvider;

    public function __construct(AdminAuthenticationProvider $adminAuthenticationProvider)
    {
        $this->adminAuthenticationProvider = $adminAuthenticationProvider;
    }

    public function encrypt(string $value): string
    {
        return base64_encode(sprintf('%s%s', $value, $this->getSalt()));
    }

    public function decrypt(string $value): string
    {
        return str_replace($this->getSalt(), '', base64_decode($value));
    }

    private function getSalt(): string
    {
        return $this->adminAuthenticationProvider->getAdminToken();
    }
}
