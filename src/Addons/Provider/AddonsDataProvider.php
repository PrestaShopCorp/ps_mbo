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

namespace PrestaShop\Module\Mbo\Addons\Provider;

use Exception;
use GuzzleHttp\Exception\ClientException;
use PrestaShop\Module\Mbo\Addons\ApiClient;
use PrestaShop\Module\Mbo\Addons\Exception\DownloadModuleException;
use PrestaShop\Module\Mbo\Addons\User\AddonsUser;
use PrestaShop\Module\Mbo\Exception\AddonsDownloadModuleException;
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;

/**
 * This class will provide data from Addons API
 */
class AddonsDataProvider implements DataProviderInterface
{
    /** @var string */
    public const ADDONS_API_MODULE_CHANNEL_STABLE = 'stable';

    /** @var string */
    public const ADDONS_API_MODULE_CHANNEL_BETA = 'beta';

    /** @var string */
    public const ADDONS_API_MODULE_CHANNEL_ALPHA = 'alpha';

    /** @var array<string> */
    public const ADDONS_API_MODULE_CHANNELS = [
        self::ADDONS_API_MODULE_CHANNEL_STABLE,
        self::ADDONS_API_MODULE_CHANNEL_BETA,
        self::ADDONS_API_MODULE_CHANNEL_ALPHA,
    ];

    /** @var array<string, string> */
    public const ADDONS_API_MODULE_ACTIONS = [
        'native' => 'getNativesModules',
        'service' => 'getServices',
        'native_all' => 'getNativesModules',
        'must-have' => 'getMustHaveModules',
        'customer' => 'getCustomerModules',
        'customer_themes' => 'getCustomerThemes',
        'check_customer' => 'getCheckCustomer',
        'check_module' => 'getCheckModule',
        'module_download' => 'getModuleZip',
        'module' => 'getModule',
        'install-modules' => 'getPreInstalledModules',
        'categories' => 'getCategories',
    ];

    /**
     * @var bool
     */
    protected static $is_addons_up = true;

    /**
     * @var ApiClient
     */
    protected $marketplaceClient;

    /**
     * @var string the cache directory location
     */
    public $cacheDir;

    /**
     * @var string
     */
    protected $moduleChannel;

    /**
     * @var AddonsUser
     */
    protected $user;

    /**
     * @param ApiClient $apiClient
     * @param AddonsUser $user
     * @param string|null $moduleChannel
     */
    public function __construct(
        ApiClient $apiClient,
        AddonsUser $user,
        ?string $moduleChannel = null
    ) {
        $this->marketplaceClient = $apiClient;
        $this->moduleChannel = $moduleChannel ?? self::ADDONS_API_MODULE_CHANNEL_STABLE;
        $this->user = $user;
    }

    /**
     * Downloads a module source from addons, store it and returns the file name.
     *
     * @throws DownloadModuleException
     */
    public function downloadModule(int $moduleId): string
    {
        $params = [
            'id_module' => $moduleId,
            'format' => 'json',
        ];

        // Module downloading
        try {
            $moduleData = $this->request('module_download', $params);
        } catch (Exception $e) {
            ErrorHelper::reportError($e);
            $message = $this->isUserAuthenticated() ?
                'Error sent by Addons. You may be not allowed to download this module.'
                : 'Error sent by Addons. You may need to be logged.';

            if ($e instanceof ClientException) {
                throw new AddonsDownloadModuleException($e);
            }
            throw new DownloadModuleException($message, 0, $e);
        }

        $temporaryZipFilename = tempnam($this->cacheDir, 'mod');
        if (file_put_contents($temporaryZipFilename, $moduleData) !== false) {
            return $temporaryZipFilename;
        } else {
            throw new DownloadModuleException('Cannot store module content in temporary file !');
        }
    }

    /**
     * Tells if the user is authenticated to Addons or Account
     */
    public function isUserAuthenticated(): bool
    {
        return $this->user->isAuthenticated();
    }

    /**
     * Tells if the user is authenticated to Addons
     */
    public function isUserAuthenticatedOnAccounts(): bool
    {
        return $this->user->hasAccountsTokenInSession() || $this->user->isConnectedOnPsAccounts();
    }

    /**
     * Returns the user's login if he is authenticated to Addons
     */
    public function getAuthenticatedUserEmail(): ?string
    {
        return $this->isUserAuthenticated() ? (string) $this->user->getEmail()['username'] : null;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function request(string $action, array $params = [])
    {
        if (!$this->isServiceUp()) {
            throw new Exception('Previous call failed and disabled client.');
        }

        if (
            !array_key_exists($action, self::ADDONS_API_MODULE_ACTIONS) ||
            !method_exists($this->marketplaceClient, self::ADDONS_API_MODULE_ACTIONS[$action])
        ) {
            throw new Exception("Action '{$action}' not found in actions list.");
        }

        $this->marketplaceClient->reset();

        // We merge the addons credentials

        $authParams = $this->getAuthenticationParams();
        if (null !== $authParams['bearer'] && is_string($authParams['bearer'])) {
            $this->marketplaceClient->setHeaders([
                'Authorization' => 'Bearer ' . $authParams['bearer'],
            ]);
        }
        if (null !== $authParams['credentials'] && is_array($authParams['credentials'])) {
            $params = array_merge($authParams['credentials'], $params);
        }

        if ($action === 'module_download') {
            $params['channel'] = $this->moduleChannel;
        } elseif ($action === 'native_all') {
            $params['iso_code'] = 'all';
        }

        try {
            return $this->marketplaceClient->{self::ADDONS_API_MODULE_ACTIONS[$action]}($params);
        } catch (Exception $e) {
            ErrorHelper::reportError($e);
            self::$is_addons_up = false;
            throw $e;
        }
    }

    public function getAuthenticationParams(): array
    {
        $authParams = [
            'bearer' => null,
            'credentials' => null,
        ];

        // We merge the addons credentials
        if ($this->isUserAuthenticated()) {
            $credentials = $this->user->getCredentials();
            if (null !== $credentials && array_key_exists('accounts_token', $credentials)) {
                $authParams['bearer'] = $credentials['accounts_token'];
                // This is a bug for now, we need to give a couple of username/password even if a token is given
                // It has to be cleaned once the bug fixed
                $authParams['credentials'] = [
                    'username' => 'name@domain.com',
                    'password' => 'fakepwd',
                ];
            } else {
                $authParams['credentials'] = $credentials;
            }
        }

        return $authParams;

    }

    public function getAccountsShopUuid(): ?string
    {
        if (!$this->isUserAuthenticatedOnAccounts()) {
            return null;
        }

        return $this->user->getAccountsShopUuid();
    }

    /**
     * Check if the previous request to Addons has failed.
     *
     * @return bool
     */
    public function isServiceUp(): bool
    {
        return self::$is_addons_up;
    }
}
