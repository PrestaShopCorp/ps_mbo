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
use PhpEncryption;
use PrestaShop\Module\Mbo\Addons\Service\ApiClient;
use PrestaShop\PrestaShop\Adapter\Module\ModuleZipManager;
use PrestaShopBundle\Service\DataProvider\Admin\AddonsInterface;
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

    /**
     * @var bool
     */
    protected static $is_addons_up = true;

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
     * @var \PrestaShop\Module\Mbo\Addons\User
     */
    private $user;

    /**
     * @param ApiClient $apiClient
     * @param ModuleZipManager $zipManager
     * @param \PrestaShop\Module\Mbo\Addons\User $user
     * @param string|null $moduleChannel
     */
    public function __construct(
        ApiClient $apiClient,
        ModuleZipManager $zipManager,
        User $user,
        ?string $moduleChannel = null
    ) {
        $this->marketplaceClient = $apiClient;
        $this->zipManager = $zipManager;
        $this->encryption = new PhpEncryption(_NEW_COOKIE_KEY_);
        $this->moduleChannel = $moduleChannel ?? self::ADDONS_API_MODULE_CHANNEL_STABLE;
        $this->user = $user;
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
            if (!$this->isAddonsAuthenticated()) {
                throw new Exception('Error sent by Addons. You may need to be logged.', 0, $e);
            } else {
                throw new Exception('Error sent by Addons. You may be not allowed to download this module.', 0, $e);
            }
        }

        $temp_filename = tempnam($this->cacheDir, 'mod');
        if (file_put_contents($temp_filename, $module_data) !== false) {
            $this->zipManager->storeInModulesFolder($temp_filename);

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

    /**
     * {@inheritdoc}
     */
    public function request($action, $params = [])
    {
        if (!$this->isAddonsUp()) {
            throw new Exception('Previous call failed and disabled client.');
        }

        // We merge the addons credentials
        if ($this->isAddonsAuthenticated()) {
            $params = array_merge($this->user->getAddonsCredentials(), $params);
        }

        $this->marketplaceClient->reset();

        try {
            switch ($action) {
                case 'native':
                    return $this->marketplaceClient->getNativesModules();
                case 'service':
                    return $this->marketplaceClient->getServices();
                case 'native_all':
                    return $this->marketplaceClient->setIsoCode('all')
                        ->getNativesModules();
                case 'must-have':
                    return $this->marketplaceClient->getMustHaveModules();
                case 'customer':
                    return $this->marketplaceClient->getCustomerModules($params['username_addons'], $params['password_addons']);
                case 'customer_themes':
                    return $this->marketplaceClient
                        ->setUserMail($params['username_addons'])
                        ->setPassword($params['password_addons'])
                        ->getCustomerThemes();
                case 'check_customer':
                    return $this->marketplaceClient
                        ->setUserMail($params['username_addons'])
                        ->setPassword($params['password_addons'])
                        ->getCheckCustomer();
                case 'check_module':
                    return $this->marketplaceClient
                        ->setUserMail($params['username_addons'])
                        ->setPassword($params['password_addons'])
                        ->setModuleName($params['module_name'])
                        ->setModuleKey($params['module_key'])
                        ->getCheckModule();
                case 'module_download':
                    if ($this->isAddonsAuthenticated()) {
                        return $this->marketplaceClient
                            ->setUserMail($params['username_addons'])
                            ->setPassword($params['password_addons'])
                            ->getModuleZip($params['id_module'], $this->moduleChannel);
                    }

                    return $this->marketplaceClient->getModuleZip($params['id_module'], $this->moduleChannel);
                case 'module':
                    return $this->marketplaceClient->getModule($params['id_module']);
                case 'install-modules':
                    return $this->marketplaceClient->getPreInstalledModules();
                case 'categories':
                    return $this->marketplaceClient->getCategories();
            }
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
