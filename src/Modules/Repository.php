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

namespace PrestaShop\Module\Mbo\Modules;

use Doctrine\Common\Cache\CacheProvider;
use Exception;
use Module as LegacyModule;
use PrestaShop\Module\Mbo\Addons\DataProvider;
use PrestaShop\PrestaShop\Adapter\Module\Module;
use Psr\Log\LoggerInterface;
use stdClass;

class Repository implements RepositoryInterface
{
    /**
     * @var DataProvider
     */
    private $dataProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ModuleDataProvider
     */
    private $moduleProvider;

    /**
     * Path to the module directory, coming from Confiuration class.
     *
     * @var string
     */
    private $moduleDirectory;

    /**
     * Key of the cache content.
     *
     * @var string
     */
    private $cacheFilePath;

    /**
     * Contains data from cache file about modules on disk.
     *
     * @var ?array
     */
    private $cache;

    /**
     * Optionnal Doctrine cache provider.
     *
     * @var CacheProvider|null
     */
    private $cacheProvider;

    public function __construct(
        DataProvider $dataProvider,
        LoggerInterface $logger,
        string $localeCode,
        CacheProvider $cacheProvider,
        string $moduleDirectory
    ) {
        $this->dataProvider = $dataProvider;
        $this->logger = $logger;
        $this->cacheName = sprintf(
            '%_addons_modules',
            $localeCode
        );

        $this->moduleDirectory = $moduleDirectory;

        // Cache related variables
        $this->cacheProvider = $cacheProvider;

        $this->clearCache();
        if ($this->cacheProvider->contains($this->cacheName)) {
            $this->cache = $this->cacheProvider->fetch($this->cacheName);
        }
    }

    public function __destruct()
    {
        if ($this->cache !== null) {
            $this->cacheProvider->save($this->cacheName, $this->cache);
        }
    }

    public function clearCache(): void
    {
        $this->cacheProvider->delete($this->cacheName);

        $this->cache = null;
    }

    public function fetchAll(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $params = ['format' => 'json'];
        $requests = [
            Filters\Origin::ADDONS_MUST_HAVE => 'must-have',
            Filters\Origin::ADDONS_SERVICE => 'service',
            Filters\Origin::ADDONS_NATIVE => 'native',
            Filters\Origin::ADDONS_NATIVE_ALL => 'native_all',
        ];

        if ($this->dataProvider->isAddonsAuthenticated()) {
            $requests[ListFilterOrigin::ADDONS_CUSTOMER] = 'customer';
        }

        $listAddonsModules = [];

        foreach ($requests as $actionFilterValue => $action) {
            var_dump($action);
            try {
                $addons = $this->dataProvider->request($action, $params);
                /** @var stdClass $addon */
                foreach ($addons as $addonsType => $addon) {
                    if (empty($addon->name)) {
                        $this->logger->error(sprintf('The addon with id %s does not have name.', $addon->id));

                        continue;
                    }

                    if (isset($listAddonsModules[$addon->name])) {
                        continue;
                    }

                    $addon->origin = $action;
                    $addon->origin_filter_value = $actionFilterValue;
                    if (isset($addon->version)) {
                        $addon->version_available = $addon->version;
                    }
                    if (!isset($addon->product_type)) {
                        $addon->productType = isset($addonsType) ? rtrim($addonsType, 's') : 'module';
                    } else {
                        $addon->productType = $addon->product_type;
                    }

                    $listAddonsModules[$addon->name] = $this->buildModule($addon);
                }
            } catch (Exception $e) {
                $this->logger->error('Data from PrestaShop Addons is invalid, and cannot fallback on cache.');
            }
        }

        $this->cache = $listAddonsModules;

        return $this->cache;
    }

    public function getModule(string $name): ?Module
    {
        $this->fetchAll();

        return $this->cache[$name] ?? null;
    }

    /**
     * Get the new module presenter class of the specified name provided.
     * It contains data from its instance, the disk, the database and from the marketplace if exists.
     *
     * @param string $name The technical name of the module
     *
     * @return Module
     */
    protected function buildModule(stdClass $module): Module
    {
        $path = $this->moduleDirectory . $module->name;
        $phpFilePath = $path . '/' . $module->name . '.php';

        var_dump('here');
        /* Data which design the module class */
        $attributes = ['name' => $name];

        // Get filemtime of module main class (We do this directly with an error suppressor to go faster)
        $currentFilemtime = (int) filemtime($phpFilePath);

        // We check that we have data from the marketplace
        try {
            $module_catalog_data = $this->dataProvider->getCatalogModules(['name' => $name]);
            $attributes = array_merge(
                $attributes,
                (array) array_shift($module_catalog_data)
            );
        } catch (Exception $e) {
            $this->logger->alert(
                'Loading data from Addons failed. %error_details%',
                ['%error_details%' => $e->getMessage()],
                'Admin.Modules.Notification'
            );
        }

        // Now, we check that cache is up to date
        if (isset($this->cache[$name]['disk']['filemtime']) &&
            $this->cache[$name]['disk']['filemtime'] === $currentFilemtime
        ) {
            // OK, cache can be loaded and used directly
            $attributes = array_merge($attributes, $this->cache[$name]['attributes']);
            $disk = $this->cache[$name]['disk'];
        } else {
            // NOPE, we have to fulfil the cache with the module data

            $disk = [
                'filemtime' => $currentFilemtime,
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

        return new Module($attributes, $disk, $database);
    }
}
