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

namespace PrestaShop\Module\Mbo\Addons;

use Exception;
use PhpEncryption;
use PrestaShop\Module\Mbo\Addons\User\AddonsUserInterface;
use PrestaShop\Module\Mbo\Service\Addons\ApiClient;
use PrestaShop\PrestaShop\Adapter\Module\ModuleZipManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * This class will provide data from Addons API
 */
class DataProvider implements AddonsInterface
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
     * @var ModuleZipManager
     */
    protected $zipManager;

    /**
     * @var PhpEncryption
     */
    protected $encryption;

    /**
     * @var string the cache directory location
     */
    public $cacheDir;

    /**
     * @var string
     */
    protected $moduleChannel;

    /**
     * @var \PrestaShop\Module\Mbo\Addons\User\AddonsUserInterface
     */
    protected $user;

    /**
     * @param ApiClient $apiClient
     * @param ModuleZipManager $zipManager
     * @param \PrestaShop\Module\Mbo\Addons\User\AddonsUserInterface $user
     * @param string|null $moduleChannel
     */
    public function __construct(
        ApiClient $apiClient,
        ModuleZipManager $zipManager,
        AddonsUserInterface $user,
        ?string $moduleChannel = null
    ) {
        $this->marketplaceClient = $apiClient;
        $this->zipManager = $zipManager;
        $this->encryption = new PhpEncryption(_NEW_COOKIE_KEY_);
        $this->moduleChannel = $moduleChannel ?? self::ADDONS_API_MODULE_CHANNEL_STABLE;
        $this->user = $user;
    }

    /**
     * {@inheritdoc}
     */
    public function downloadModule(int $moduleId): bool
    {
        $params = [
            'id_module' => $moduleId,
            'format' => 'json',
        ];

        // Module downloading
        try {
            $moduleData = $this->request('module_download', $params);
        } catch (Exception $e) {
            $message = $this->isAddonsAuthenticated() ?
                'Error sent by Addons. You may be not allowed to download this module.'
                : 'Error sent by Addons. You may need to be logged.';

            throw new Exception($message, 0, $e);
        }

        $temporaryZipFilename = tempnam($this->cacheDir, 'mod');
        if (file_put_contents($temporaryZipFilename, $moduleData) !== false) {
            // Here we unzip the module in PS folder. Do we have to do it or is it PS purpose ?
            $this->zipManager->storeInModulesFolder($temporaryZipFilename);

            return true;
        } else {
            throw new Exception('Cannot store module content in temporary folder !');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isAddonsAuthenticated(): bool
    {
        return $this->user->isAddonsAuthenticated();
    }

    public function getAddonsEmail(): ?string
    {
        return $this->isAddonsAuthenticated() ? (string) $this->user->getAddonsEmail()['username_addons'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function request($action, $params = [])
    {
        if (!$this->isAddonsUp()) {
            throw new Exception('Previous call failed and disabled client.');
        }

        if (!array_key_exists($action, self::ADDONS_API_MODULE_ACTIONS) ||
            !method_exists($this->marketplaceClient, self::ADDONS_API_MODULE_ACTIONS[$action])) {
            throw new Exception("Action '{$action}' not found in actions list.");
        }

        // We merge the addons credentials
        if ($this->isAddonsAuthenticated()) {
            $params = array_merge($this->user->getAddonsCredentials(), $params);
        }

        if ($action === 'module_download') {
            $params['channel'] = $this->moduleChannel;
        } elseif ($action === 'native_all') {
            $params['iso_code'] = 'all';
        }

        $this->marketplaceClient->reset();

        try {
            return $this->marketplaceClient->{self::ADDONS_API_MODULE_ACTIONS[$action]}($params);
        } catch (Exception $e) {
            self::$is_addons_up = false;

            throw $e;
        }
    }

    /**
     * Check if a request has already failed.
     *
     * @return bool
     */
    public function isAddonsUp(): bool
    {
        return self::$is_addons_up;
    }
}
