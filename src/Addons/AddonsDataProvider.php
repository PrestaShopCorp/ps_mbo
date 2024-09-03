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

namespace PrestaShop\Module\Mbo\Addons;

use Exception;
use GuzzleHttp\Exception\ClientException;
use PhpEncryption;
use PrestaShop\Module\Mbo\Addons\Exception\DownloadModuleException;
use PrestaShop\Module\Mbo\Addons\User\AddonsUser;
use PrestaShop\Module\Mbo\Exception\AddonsDownloadModuleException;
use PrestaShop\Module\Mbo\Helpers\ErrorHelper;
use PrestaShop\PrestaShop\Adapter\Module\ModuleZipManager;
use PrestaShopBundle\Service\DataProvider\Admin\AddonsInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Data provider for new Architecture, about Addons.
 *
 * This class will provide data from Addons API
 */
class AddonsDataProvider implements AddonsInterface
{
    /** @var string */
    const ADDONS_API_MODULE_CHANNEL_STABLE = 'stable';

    /** @var array<string, string> */
    const ADDONS_API_MODULE_ACTIONS = [
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
    protected static $isAddonsUp = true;

    /**
     * @var ApiClient
     */
    private $marketplaceClient;

    /**
     * @var ModuleZipManager
     */
    private $zipManager;

    /**
     * @var PhpEncryption
     */
    private $encryption;

    /**
     * @var string the cache directory location
     */
    public $cacheDir;

    /**
     * @var string
     */
    private $moduleChannel;
    /**
     * @var AddonsUser
     */
    private $user;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param ApiClient $apiClient
     * @param ModuleZipManager $zipManager
     * @param AddonsUser $user
     * @param string|null $moduleChannel
     */
    public function __construct(
        ApiClient $apiClient,
        ModuleZipManager $zipManager,
        AddonsUser $user,
        TranslatorInterface $translator,
        string $moduleChannel = null
    ) {
        $this->marketplaceClient = $apiClient;
        $this->zipManager = $zipManager;
        $this->encryption = new PhpEncryption(_NEW_COOKIE_KEY_);
        if (null === $moduleChannel) {
            $moduleChannel = self::ADDONS_API_MODULE_CHANNEL_STABLE;
        }
        $this->user = $user;
        $this->translator = $translator;
        $this->moduleChannel = $moduleChannel;
    }

    /**
     * @param int $module_id
     *
     * @return bool
     *
     * @throws Exception
     */
    public function downloadModule(int $module_id): bool
    {
        $params = [
            'id_module' => $module_id,
            'format' => 'json',
        ];

        // Module downloading
        try {
            $module_data = $this->request('module_download', $params);
        } catch (Exception $e) {
            ErrorHelper::reportError($e);
            $message = $this->isUserAuthenticated() ?
                'Error sent by Addons. You may be not allowed to download this module.'
                : 'Error sent by Addons. You may need to be logged.';

            if ($e instanceof ClientException) {
                $e = new AddonsDownloadModuleException($e);
                $message = $this->translator->trans($e->getMessage(), [], 'Modules.Mbo.Errors');
            }

            throw new DownloadModuleException($message, 0, $e);
        }

        $temp_filename = tempnam($this->cacheDir, 'mod');
        if (file_put_contents($temp_filename, $module_data) !== false) {
            $this->zipManager->storeInModulesFolder($temp_filename);

            return true;
        } else {
            throw new DownloadModuleException('Cannot store module content in temporary folder !');
        }
    }

    /**
     * @return bool
     *
     * @todo Does this function should be in a User related class ?
     */
    public function isAddonsAuthenticated(): bool
    {
        return $this->isUserAuthenticated();
    }

    /**
     * Tells if the user is authenticated to Addons or Account
     *
     * @return bool
     */
    public function isUserAuthenticated()
    {
        return $this->user->isAuthenticated();
    }

    /**
     * Tells if the user is authenticated to Addons
     *
     * @return bool
     */
    public function isUserAuthenticatedOnAccounts()
    {
        return $this->user->hasAccountsTokenInSession() || $this->user->isConnectedOnPsAccounts();
    }

    /**
     * Returns the user's login if he is authenticated to Addons
     *
     * @return string|null
     *
     * @throws Exception
     */
    public function getAuthenticatedUserEmail()
    {
        return $this->isUserAuthenticated() ? (string) $this->user->getEmail()['username'] : null;
    }

    /**
     * @param string $action
     * @param array $params
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function request($action, $params = [])
    {
        if (!$this->isAddonsUp()) {
            throw new Exception('Previous call failed and disabled client.');
        }

        if (
            !array_key_exists($action, self::ADDONS_API_MODULE_ACTIONS) ||
            !method_exists($this->marketplaceClient, self::ADDONS_API_MODULE_ACTIONS[$action])
        ) {
            throw new Exception("Action '{$action}' not found in actions list.");
        }

        $this->marketplaceClient->reset();

        $authParams = $this->getAuthenticationParams();
        if (isset($authParams['bearer']) && is_string($authParams['bearer'])) {
            $this->marketplaceClient->setHeaders([
                'Authorization' => 'Bearer ' . $authParams['bearer'],
            ]);
        }
        if (is_array($authParams['credentials']) && !empty($authParams['credentials'])) {
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
            self::$isAddonsUp = false;
            throw $e;
        }
    }

    /**
     * @return array
     *
     * @throws Exception
     */
    protected function getAddonsCredentials()
    {
        $request = Request::createFromGlobals();
        $username = $this->encryption->decrypt($request->cookies->get('username_addons'));
        $password = $this->encryption->decrypt($request->cookies->get('password_addons'));

        return [
            'username_addons' => $username,
            'password_addons' => $password,
        ];
    }

    /** Does this function should be in a User related class ? **/
    public function getAddonsEmail()
    {
        $request = Request::createFromGlobals();
        $username = $this->encryption->decrypt($request->cookies->get('username_addons'));

        return [
            'username_addons' => $username,
        ];
    }

    /**
     * @return array
     */
    public function getAuthenticationParams()
    {
        $authParams = [
            'bearer' => null,
            'credentials' => null,
        ];

        // We merge the addons credentials
        if ($this->isUserAuthenticated()) {
            $credentials = $this->user->getCredentials();
            if (array_key_exists('accounts_token', $credentials)) {
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

    /**
     * Check if a request has already failed.
     *
     * @return bool
     */
    public function isAddonsUp(): bool
    {
        return self::$isAddonsUp;
    }
}
