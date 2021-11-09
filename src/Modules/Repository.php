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

use Db;
use Doctrine\Common\Cache\CacheProvider;
use Exception;
use Module as LegacyModule;
use PhpParser;
use PrestaShop\Module\Mbo\Addons\DataProvider;
use Psr\Log\LoggerInterface;
use Shop;
use stdClass;
use Validate;

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
        string $moduleDirectory,
        string $dbPrefix
    ) {
        $this->dataProvider = $dataProvider;
        $this->dbPrefix = $dbPrefix;
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
        // if ($this->cache !== null) {
        //     return $this->cache;
        // }

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
                        $addon->product_type = isset($addonsType) ? rtrim($addonsType, 's') : 'module';
                    } else {
                        $addon->product_type = $addon->product_type;
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
        $filePath = $this->getModulePath($module->name);
        $moduleIsPresentOnDisk = file_exists($filePath);

        /* Convert module to array */
        $attributes = json_decode(json_encode($module), true);

        // Get filemtime of module main class (We do this directly with an error suppressor to go faster)
        $currentFilemtime = $moduleIsPresentOnDisk ? (int) filemtime($filePath) : 0;

        // Now, we check that cache is up to date
        if ($currentFilemtime !== 0
            && isset($this->cache[$module->name]['disk']['filemtime'])
            && $this->cache[$module->name]['disk']['filemtime'] === $currentFilemtime
        ) {
            // OK, cache can be loaded and used directly
            $attributes = array_merge($attributes, $this->cache[$module->name]['attributes']);
            $disk = $this->cache[$module->name]['disk'];
        } else {
            // NOPE, we have to fulfil the cache with the module data

            $disk = [
                'filemtime' => $currentFilemtime,
                'is_present' => $moduleIsPresentOnDisk,
                'is_valid' => 0,
                'version' => null,
                'path' => $this->moduleDirectory . $module->name,
            ];
            $main_class_attributes = [];

            if ($this->isModuleMainClassValid($module->name)) {
                // We load the main class of the module, and get its properties
                $tmp_module = LegacyModule::getInstanceByName($module->name);
                foreach (['warning', 'name', 'tab', 'displayName', 'description', 'author', 'author_address',
                    'limited_countries', 'need_instance', 'confirmUninstall', ] as $data_to_get) {
                    if (isset($tmp_module->{$data_to_get})) {
                        $main_class_attributes[$data_to_get] = $tmp_module->{$data_to_get};
                    }
                }

                $main_class_attributes['parent_class'] = get_parent_class($module->name);
                $main_class_attributes['is_paymentModule'] = is_subclass_of($module->name, 'PaymentModule');
                $main_class_attributes['is_configurable'] = (int) method_exists($tmp_module, 'getContent');

                $disk['is_valid'] = 1;
                $disk['version'] = $tmp_module->version;

                $attributes = array_merge($attributes, $main_class_attributes);
            } else {
                $disk['is_valid'] = 1;
            }

            $this->cache[$module->name]['attributes'] = $main_class_attributes;
            $this->cache[$module->name]['disk'] = $disk;
        }

        // Get data from database
        $database = $this->findInDatabaseByName($module->name) ?? [];

        return new Module($attributes, $disk, $database);
    }

    /**
     * We won't load an invalid class. This function will check any potential parse error.
     *
     * @param string $name The technical module name to check
     *
     * @return bool true if valid
     */
    protected function isModuleMainClassValid(string $name): bool
    {
        if (!Validate::isModuleName($name)) {
            return false;
        }

        $filePath = $this->getModulePath($name);
        // Check if file exists (slightly faster than file_exists)
        if (!file_exists($filePath)) {
            return false;
        }

        $parser = (new PhpParser\ParserFactory())->create(PhpParser\ParserFactory::ONLY_PHP7);
        $log_context_data = [
            'object_type' => 'Module',
            'object_id' => LegacyModule::getModuleIdByName($name),
        ];

        try {
            $parser->parse(file_get_contents($filePath));
        } catch (PhpParser\Error $exception) {
            $this->logger->critical(
                $this->translator->trans(
                    'Parse error detected in main class of module %module%: %parse_error%',
                    [
                        '%module%' => $name,
                        '%parse_error%' => $exception->getMessage(),
                    ],
                    'Admin.Modules.Notification'
                ),
                $log_context_data
            );

            return false;
        }

        $logger = $this->logger;
        // -> Even if we do not detect any parse error in the file, we may have issues
        // when trying to load the file. (i.e with additional require_once).
        // -> We use an anonymous function here because if a test is made twice
        // on the same module, the test on require_once would immediately return true
        // (as the file would have already been evaluated).
        $requireCorrect = function ($name) use ($filePath, $logger, $log_context_data) {
            try {
                require_once $filePath;
            } catch (Exception $e) {
                $logger->error(
                    $this->translator->trans(
                        'Error while loading file of module %module%. %error_message%',
                        [
                            '%module%' => $name,
                            '%error_message%' => $e->getMessage(), ],
                        'Admin.Modules.Notification'
                    ),
                    $log_context_data
                );

                return false;
            }

            return true;
        };

        return $requireCorrect($name);
    }

    protected function findInDatabaseByName(string $name): ?array
    {
        $result = Db::getInstance()->getRow(
            'SELECT `id_module` as `id`, `active`, `version` FROM `' . _DB_PREFIX_ . 'module` WHERE `name` = "' . pSQL($name) . '"'
        );

        if (!is_array($result)) {
            return null;
        }

        $enableStatuses = $this->getEnableStatuses($name);
        $result['installed'] = true;
        $result['active'] = (bool) (($result['active'] ?? false) && ($result['shop_active'] ?? false));
        $result['active_on_mobile'] = (bool) ((int) ($enableStatuses['enable_device'] ?? 0) & Filters\Device::MOBILE);

        return $result;
    }

    /**
     * Check if a module is enabled in the current shop context.
     *
     * @param string $name The technical module name
     *
     * @return bool
     */
    protected function getEnableStatuses(string $name)
    {
        $id_shops = Shop::getContextListShopID();

        return Db::getInstance()->getRow(
            'SELECT m.`id_module` as `active`, ms.`id_module` as `shop_active`, ms.`enable_device` as `enable_device`' .
            'FROM `' . _DB_PREFIX_ . 'module` m ' .
            'LEFT JOIN `' . _DB_PREFIX_ . 'module_shop` ms ON m.`id_module` = ms.`id_module` ' .
            'WHERE `name` = "' . pSQL($name) . '" ' .
            'AND ms.`id_shop` IN (' . implode(',', array_map('intval', $id_shops)) . ')');
    }

    protected function getModulePath(string $name): string
    {
        return $this->moduleDirectory . $name . '/' . $name . '.php';
    }
}
