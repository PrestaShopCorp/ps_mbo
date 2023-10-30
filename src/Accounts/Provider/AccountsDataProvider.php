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

use Db;
use Exception;
use PrestaShop\Module\PsAccounts\Repository\UserTokenRepository;
use PrestaShop\PrestaShop\Adapter\ServiceLocator;
use PrestaShop\PsAccountsInstaller\Installer\Exception\ModuleNotInstalledException;
use PrestaShop\PsAccountsInstaller\Installer\Exception\ModuleVersionException;
use PrestaShop\PsAccountsInstaller\Installer\Facade\PsAccounts;
use PrestaShop\PsAccountsInstaller\Installer\Installer;

class AccountsDataProvider
{
    /**
     * @var string
     */
    private $psAccountsVersion;

    public function __construct(
        string $psAccountsVersion
    )
    {
        $this->psAccountsVersion = $psAccountsVersion;
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

        try {
            $shopUuid = $this->getAccountsService()->getShopUuid();
        } catch (Exception $e) {
            $shopUuid = null;
        }

        return $shopUuid ?: null;
    }

    public function getAccountsUserId(): ?string
    {
        try {
            $userUuid = $this->getAccountsService()->getUserUuid();
        } catch (Exception $e) {
            $userUuid = null;
        }

        return $userUuid ?: null;
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


    /**
     * @param string $serviceName
     *
     * @return mixed
     *
     * @throws ModuleNotInstalledException
     * @throws ModuleVersionException
     */
    public function getAccountsService()
    {
        if ($this->isPsAccountsInstalled()) {
            if ($this->checkPsAccountsVersion()) {
                return \Module::getInstanceByName(Installer::PS_ACCOUNTS_MODULE_NAME)
                    ->getService(PsAccounts::PS_ACCOUNTS_SERVICE);
            }
            throw new ModuleVersionException('Module version expected : ' . $this->psAccountsVersion);
        }
        throw new ModuleNotInstalledException('Module not installed : ' . Installer::PS_ACCOUNTS_MODULE_NAME);
    }

    /**
     * @return bool
     */
    private function isPsAccountsInstalled()
    {
        $moduleName = Installer::PS_ACCOUNTS_MODULE_NAME;

        if (false === $this->isShopVersion17()) {
            return \Module::isInstalled($moduleName);
        }

        $sqlQuery = 'SELECT `id_module` FROM `' . _DB_PREFIX_ . 'module` WHERE `name` = "' . pSQL($moduleName) . '" AND `active` = 1';

        return (int) Db::getInstance()->getValue($sqlQuery) > 0;
    }

    private function checkPsAccountsVersion()
    {
        $moduleName = Installer::PS_ACCOUNTS_MODULE_NAME;

        $module = \Module::getInstanceByName($moduleName);

        if ($module instanceof \Ps_accounts) {
            return version_compare(
                $module->version,
                $this->psAccountsVersion,
                '>='
            );
        }

        return false;
    }

    /**
     * @return bool
     */
    private function isShopVersion17()
    {
        return version_compare(_PS_VERSION_, '1.7.0.0', '>=');
    }
}
