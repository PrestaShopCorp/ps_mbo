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

namespace PrestaShop\Module\Mbo\Module;

use Db;
use Doctrine\Common\Cache\CacheProvider;
use Exception;
use PrestaShop\Module\Mbo\Addons\Provider\AddonsDataProvider;
use Psr\Log\LoggerInterface;
use Shop;
use stdClass;

/**
 * Retrieves modules' raw information from Addons and database
 */
class Repository implements RepositoryInterface
{
    /**
     * @var AddonsDataProvider
     */
    protected $dataProvider;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ModuleBuilder
     */
    protected $moduleBuilder;

    /**
     * Contains data from cache file about modules on disk.
     *
     * @var ?array<int|string, Module>
     */
    protected $cache;

    /**
     * Optionnal Doctrine cache provider.
     *
     * @var CacheProvider|null
     */
    protected $cacheProvider;

    /**
     * @var string
     */
    protected $cacheName;

    /**
     * @var string
     */
    protected $dbPrefix;

    public function __construct(
        AddonsDataProvider $dataProvider,
        ModuleBuilder $moduleBuilder,
        LoggerInterface $logger,
        string $localeCode,
        CacheProvider $cacheProvider,
        string $dbPrefix
    ) {
        $this->dataProvider = $dataProvider;
        $this->dbPrefix = $dbPrefix;
        $this->logger = $logger;

        $this->cacheName = sprintf(
            '%s_addons_modules',
            $localeCode
        );

        $this->moduleBuilder = $moduleBuilder;

        // Cache related variables
        $this->cacheProvider = $cacheProvider;

        if ($this->cacheProvider->contains($this->cacheName)) {
            $this->cache = $this->cacheProvider->fetch($this->cacheName);
        }
    }

    public function __destruct()
    {
        if ($this->cache !== null) {
            $this->cacheProvider->save($this->cacheName, $this->cache, 60 * 60 * 24);
        }
    }

    public function clearCache(): void
    {
        $this->cacheProvider->delete($this->cacheName);
        $this->cache = null;
    }

    public function fetchAll(bool $rawModules = false): array
    {
        if ($this->cache !== null && !$rawModules) {
            return $this->cache;
        }

        $params = ['format' => 'json'];
        $requests = [
            Filters\Origin::ADDONS_MUST_HAVE => 'must-have',
            Filters\Origin::ADDONS_SERVICE => 'service',
            Filters\Origin::ADDONS_NATIVE => 'native',
            Filters\Origin::ADDONS_NATIVE_ALL => 'native_all',
        ];

        if ($this->dataProvider->isUserAuthenticated()) {
            $requests[Filters\Origin::ADDONS_CUSTOMER] = 'customer';
        }

        $listAddonsModules = [];
        $apiModules = [];

        foreach ($requests as $actionFilterValue => $action) {
            try {
                $addons = $this->dataProvider->request($action, $params);
                /** @var stdClass $addon */
                foreach ($addons as $addonsType => $addon) {
                    if (empty($addon->name)) {
                        $this->logger->error(sprintf('The addon with id %s does not have name.', $addon->id));

                        continue;
                    }

                    if (isset($listAddonsModules[$addon->name]) || ($rawModules && isset($apiModules[$addon->name]))) {
                        continue;
                    }

                    $addon->origin = $action;
                    $addon->origin_filter_value = $actionFilterValue;
                    if (isset($addon->version)) {
                        $addon->version_available = $addon->version;
                    }
                    if (!isset($addon->product_type)) {
                        $addon->product_type = is_string($addonsType) ? rtrim($addonsType, 's') : 'module';
                    }

                    if ($rawModules) {
                        $apiModules[$addon->name] = $addon;
                    } else {
                        $listAddonsModules[$addon->name] = $this->moduleBuilder->build(
                            $addon,
                            $this->findInDatabaseByName($addon->name)
                        );
                    }
                }
            } catch (Exception $e) {
                $this->logger->error('Data from PrestaShop Addons is invalid, and cannot fallback on cache.');
            }
        }

        if ($rawModules) {
            return $apiModules;
        }
        $this->cache = $listAddonsModules;

        return $this->cache;
    }

    public function getApiModule(string $name): ?stdClass
    {
        $modules = $this->fetchAll(true);
        if (array_key_exists($name, $modules)) {
            return $modules[$name];
        }

        return null;
    }

    public function getModule(string $name): ?Module
    {
        $this->fetchAll();

        $module = $this->cache[$name] ?? null;

        if ($module) {
            $this->moduleBuilder->generateAddonsUrls($module);
        }

        return $module;
    }

    /**
     * @param int $moduleId
     *
     * @return array
     */
    public function getModuleAttributesById(int $moduleId): array
    {
        return (array) $this->dataProvider->request('module', ['id_module' => $moduleId]);
    }

    /**
     * Send request to get module details on the marketplace, then merge the data received in Module instance.
     *
     * @param int $moduleId
     *
     * @return Module
     */
    public function getModuleById(int $moduleId): ?Module
    {
        $moduleAttributes = $this->getModuleAttributesById($moduleId);

        $module = $this->getModule($moduleAttributes['name']);

        foreach ($moduleAttributes as $name => $value) {
            if (!$module->attributes->has($name)) {
                $module->attributes->set($name, $value);
            }
        }

        return $module;
    }

    public function findInDatabaseByName(string $name): ?array
    {
        $result = Db::getInstance()->getRow(
            'SELECT `id_module` as `id`, `active`, `version` FROM `' . $this->dbPrefix . 'module` WHERE `name` = "' . pSQL($name) . '"'
        );

        if (!is_array($result)) {
            return null;
        }

        $enableStatuses = Db::getInstance()->getRow(
            'SELECT m.`id_module` as `active`, ms.`id_module` as `shop_active`, ms.`enable_device` as `enable_device`' .
            'FROM `' . $this->dbPrefix . 'module` m ' .
            'LEFT JOIN `' . $this->dbPrefix . 'module_shop` ms ON m.`id_module` = ms.`id_module` ' .
            'WHERE `name` = "' . pSQL($name) . '" ' .
            'AND ms.`id_shop` IN (' . implode(',', array_map('intval', Shop::getContextListShopID())) . ')');

        $result['installed'] = true;
        $result['active'] = ($result['active'] ?? false) && ($enableStatuses['shop_active'] ?? false);
        $result['active_on_mobile'] = (bool) ((int) ($enableStatuses['enable_device'] ?? 0) & Filters\Device::MOBILE);

        return $result;
    }
}
