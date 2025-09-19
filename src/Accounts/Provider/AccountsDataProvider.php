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

use PrestaShop\PsAccountsInstaller\Installer\Exception\InstallerException;
use PrestaShop\PsAccountsInstaller\Installer\Facade\PsAccounts;
use PrestaShop\PsAccountsInstaller\Installer\Installer;

if (!defined('_PS_VERSION_')) {
    exit;
}

class AccountsDataProvider
{
    private $psAccountsService;
    private $psAccountsVersion;
    private $moduleName;

    public function __construct(string $psAccountsVersion)
    {
        $this->psAccountsVersion = $psAccountsVersion;
        $this->moduleName = Installer::PS_ACCOUNTS_MODULE_NAME;
        try {
            $this->psAccountsService = $this->getService(PsAccounts::PS_ACCOUNTS_SERVICE);
        } catch (InstallerException $e) {
            $this->psAccountsService = null;
        }
    }

    /**
     * Get PsAccounts User Token
     *
     * @return string
     */
    public function getAccountsToken(): string
    {
        if (!$this->isAccountLinked()) {
            return '';
        }

        if ($this->psAccountsService && method_exists($this->psAccountsService, 'getUserToken')) {
            $token = $this->psAccountsService->getUserToken();

            return null === $token ? '' : (string) $token;
        }

        try {
            // @phpstan-ignore class.notFound
            $accountsUserTokenRepository = $this->getService(\PrestaShop\Module\PsAccounts\Repository\UserTokenRepository::class);
            $token = $accountsUserTokenRepository->getOrRefreshToken();

            return null === $token ? '' : (string) $token;
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * @return string|null
     */
    public function getAccountsShopId(): ?string
    {
        $shopUuid = null;
        if ($this->psAccountsService && method_exists($this->psAccountsService, 'getShopUuid')) {
            $shopUuid = $this->psAccountsService->getShopUuid();
        }

        return $shopUuid ?: null;
    }

    /**
     * @return string|null
     */
    public function getAccountsUserId(): ?string
    {
        $userUuid = null;
        if ($this->psAccountsService && method_exists($this->psAccountsService, 'getUserUuid')) {
            $userUuid = $this->psAccountsService->getUserUuid();
        }

        return $userUuid ?: null;
    }

    /**
     * @return string|null
     */
    public function getAccountsUserEmail(): ?string
    {
        if (!$this->psAccountsService) {
            return null;
        }

        return $this->psAccountsService->getEmail();
    }

    /**
     * Get Hydra ps_accounts shop token, available since ps_accounts 7.1.1
     *
     * @return string
     */
    public function getShopTokenV7(): string
    {
        if (!$this->psAccountsService) {
            return '';
        }

        $shopToken = null;
        if (method_exists($this->psAccountsService, 'getShopToken')) {
            try {
                $shopToken = $this->psAccountsService->getShopToken();
            } catch (\Exception $e) {
            }
        }

        return $shopToken ?: '';
    }

    /**
     * Get ps_accounts shop token firebase
     *
     * @return string
     */
    public function getAccountsShopToken(): string
    {
        if (!$this->psAccountsService) {
            return '';
        }

        $shopToken = null;
        try {
            $shopToken = $this->psAccountsService->getOrRefreshToken();
        } catch (\Exception $e) {
        }

        return $shopToken ?: '';
    }

    /**
     * @return bool
     */
    private function isAccountLinked(): bool
    {
        if (!$this->psAccountsService) {
            return false;
        }

        return $this->psAccountsService->isAccountLinked();
    }

    /**
     * @param string $serviceName
     *
     * @return mixed|null
     */
    private function getService(string $serviceName)
    {
        $service = null;
        $module = null;

        if (\Module::isInstalled($this->moduleName) && $this->checkPsAccountsVersion()) {
            $module = \Module::getInstanceByName($this->moduleName);
        }

        if ($module && method_exists($module, 'getService')) {
            $service = $module->getService($serviceName);
        }

        return $service;
    }

    private function checkPsAccountsVersion(): bool
    {
        $module = \Module::getInstanceByName($this->moduleName);

        return version_compare(
            $module->version,
            $this->psAccountsVersion,
            '>='
        );
    }
}
