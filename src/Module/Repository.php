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

namespace PrestaShop\Module\Mbo\Module;

use Doctrine\Common\Cache\CacheProvider;
use Exception;
use ParseError;
use Module as LegacyModule;
use PrestaShop\Module\Mbo\Addons\AddonsCollection;
use PrestaShop\Module\Mbo\Module\Filter;
use PrestaShop\PrestaShop\Adapter\Module\Module;
use PrestaShop\PrestaShop\Adapter\Module\ModuleDataUpdater;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\DoctrineProvider;
use Symfony\Component\Translation\TranslatorInterface;
use PrestaShop\Module\Mbo\Addons\Service\ApiClient;

class Repository implements RepositoryInterface
{
    /**
     * @var AdminModuleDataProvider
     */
    private $apiClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ModuleDataProvider
     */
    private $moduleProvider;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * Path to the module directory, coming from Confiuration class.
     *
     * @var string
     */
    private $modulePath;

    /**
     * Key of the cache content.
     *
     * @var string
     */
    private $cacheFilePath;

    /**
     * Contains data from cache file about modules on disk.
     *
     * @var array
     */
    private $cache = [];

    /**
     * Optionnal Doctrine cache provider.
     *
     * @var CacheProvider|null
     */
    private $cacheProvider;

    /**
     * Keep loaded modules in cache.
     *
     * @var DoctrineProvider
     */
    private $loadedModules;

    public function __construct(
        ApiClient $apiClient,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        CacheProvider $cacheProvider = null
    ) {
        $this->apiClient = $apiClient;
        $this->logger = $logger;
        $this->translator = $translator;

        list($isoLang) = explode('-', $translator->getLocale());

        // Cache related variables
        $this->cacheFilePath = $isoLang . '_local_modules';
        $this->cacheProvider = $cacheProvider;
        $this->loadedModules = new DoctrineProvider(new ArrayAdapter());

        if ($this->cacheProvider && $this->cacheProvider->contains($this->cacheFilePath)) {
            $this->cache = $this->cacheProvider->fetch($this->cacheFilePath);
        }
    }

    public function __destruct()
    {
        if ($this->cacheProvider) {
            $this->cacheProvider->save($this->cacheFilePath, $this->cache);
        }
    }

    public function clearCache()
    {
        if ($this->cacheProvider) {
            $this->cacheProvider->delete($this->cacheFilePath);
        }

        $this->cache = [];
    }


    public function fetchAll(): array
    {
        if ($this->cacheProvider && $this->cacheProvider->contains($this->languageISO . self::_CACHEKEY_MODULES_)) {
            $this->catalog_modules = $this->cacheProvider->fetch($this->languageISO . self::_CACHEKEY_MODULES_);
        }

        if (!$this->catalog_modules) {
            $params = ['format' => 'json'];
            $requests = [
                ListFilterOrigin::ADDONS_MUST_HAVE => 'must-have',
                ListFilterOrigin::ADDONS_SERVICE => 'service',
                ListFilterOrigin::ADDONS_NATIVE => 'native',
                ListFilterOrigin::ADDONS_NATIVE_ALL => 'native_all',
            ];
            if ($this->addonsDataProvider->isAddonsAuthenticated()) {
                $requests[ListFilterOrigin::ADDONS_CUSTOMER] = 'customer';
            }

            try {
                $listAddons = [];
                // We execute each addons request
                foreach ($requests as $action_filter_value => $action) {
                    if (!$this->addonsDataProvider->isAddonsUp()) {
                        continue;
                    }
                    // We add the request name in each product returned by Addons,
                    // so we know whether is bought

                    $addons = $this->addonsDataProvider->request($action, $params);
                    /** @var \stdClass $addon */
                    foreach ($addons as $addonsType => $addon) {
                        if (empty($addon->name)) {
                            $this->logger->error(sprintf('The addon with id %s does not have name.', $addon->id));

                            continue;
                        }

                        $addon->origin = $action;
                        $addon->origin_filter_value = $action_filter_value;
                        $addon->categoryParent = $this->categoriesProvider
                            ->getParentCategory($addon->categoryName);
                        if (isset($addon->version)) {
                            $addon->version_available = $addon->version;
                        }
                        if (!isset($addon->product_type)) {
                            $addon->productType = isset($addonsType) ? rtrim($addonsType, 's') : 'module';
                        } else {
                            $addon->productType = $addon->product_type;
                        }
                        $listAddons[$addon->name] = $addon;
                    }
                }

                if (!empty($listAddons)) {
                    $this->catalog_modules = $listAddons;
                    if ($this->cacheProvider) {
                        $this->cacheProvider->save($this->languageISO . self::_CACHEKEY_MODULES_, $this->catalog_modules, self::_DAY_IN_SECONDS_);
                    }
                } else {
                    $this->fallbackOnCatalogCache();
                }
            } catch (\Exception $e) {
                if (!$this->fallbackOnCatalogCache()) {
                    $this->logger->error('Data from PrestaShop Addons is invalid, and cannot fallback on cache.');
                }
            }
        }

        return $modules;
    }

    /**
     * Get the new module presenter class of the specified name provided.
     * It contains data from its instance, the disk, the database and from the marketplace if exists.
     *
     * @param string $name The technical name of the module
     * @param bool $skip_main_class_attributes
     *
     * @return Module
     */
    public function getModule($name, $skip_main_class_attributes = false): Module
    {
        if ($this->loadedModules->contains($name)) {
            return $this->loadedModules->fetch($name);
        }

        $path = $this->modulePath . $name;
        $php_file_path = $path . '/' . $name . '.php';

        /* Data which design the module class */
        $attributes = ['name' => $name];

        // Get filemtime of module main class (We do this directly with an error suppressor to go faster)
        $current_filemtime = (int) @filemtime($php_file_path);

        // We check that we have data from the marketplace
        try {
            $module_catalog_data = $this->apiClient->getCatalogModules(['name' => $name]);
            $attributes = array_merge(
                $attributes,
                (array) array_shift($module_catalog_data)
            );
        } catch (Exception $e) {
            $this->logger->alert(
                $this->translator->trans(
                    'Loading data from Addons failed. %error_details%',
                    ['%error_details%' => $e->getMessage()],
                    'Admin.Modules.Notification'
                )
            );
        }

        // Now, we check that cache is up to date
        if (isset($this->cache[$name]['disk']['filemtime']) &&
            $this->cache[$name]['disk']['filemtime'] === $current_filemtime
        ) {
            // OK, cache can be loaded and used directly
            $attributes = array_merge($attributes, $this->cache[$name]['attributes']);
            $disk = $this->cache[$name]['disk'];
        } else {
            // NOPE, we have to fulfil the cache with the module data

            $disk = [
                'filemtime' => $current_filemtime,
                'is_present' => (int) $this->moduleProvider->isOnDisk($name),
                'is_valid' => 0,
                'version' => null,
                'path' => $path,
            ];
            $main_class_attributes = [];

            if (!$skip_main_class_attributes && $this->moduleProvider->isModuleMainClassValid($name)) {
                // We load the main class of the module, and get its properties
                $tmp_module = LegacyModule::getInstanceByName($name);
                foreach (['warning', 'name', 'tab', 'displayName', 'description', 'author', 'author_address',
                    'limited_countries', 'need_instance', 'confirmUninstall', ] as $data_to_get) {
                    if (isset($tmp_module->{$data_to_get})) {
                        $main_class_attributes[$data_to_get] = $tmp_module->{$data_to_get};
                    }
                }

                $main_class_attributes['parent_class'] = get_parent_class($name);
                $main_class_attributes['is_paymentModule'] = is_subclass_of($name, 'PaymentModule');
                $main_class_attributes['is_configurable'] = (int) method_exists($tmp_module, 'getContent');

                $disk['is_valid'] = 1;
                $disk['version'] = $tmp_module->version;

                $attributes = array_merge($attributes, $main_class_attributes);
            } elseif (!$skip_main_class_attributes) {
                $main_class_attributes['warning'] = 'Invalid module class';
            } else {
                $disk['is_valid'] = 1;
            }

            $this->cache[$name]['attributes'] = $main_class_attributes;
            $this->cache[$name]['disk'] = $disk;
        }

        // Get data from database
        $database = $this->moduleProvider->findByName($name);

        $module = new Module($attributes, $disk, $database);
        $this->loadedModules->save($name, $module);

        return $module;
    }
}
