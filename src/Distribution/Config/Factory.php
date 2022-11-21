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

namespace PrestaShop\Module\Mbo\Distribution\Config;

use Db;
use Doctrine\DBAL\Query\QueryException;
use PrestaShop\Module\Mbo\Distribution\Config\Exception\CannotSaveConfigException;
use PrestaShop\Module\Mbo\Distribution\Config\Exception\InvalidConfigException;
use PrestaShopDatabaseException;

final class Factory
{
    public function __construct()
    {
        $this->db = Db::getInstance();
    }

    /**
     * This method will receive an array of config, validate its structure,
     * build a collection of config objects and save it in DB.
     *
     * @param array $config
     *
     * @throws QueryException
     * @throws InvalidConfigException
     * @throws CannotSaveConfigException
     * @throws PrestaShopDatabaseException
     */
    public function buildAndSave(array $config)
    {
        if (!$this->assertConfigIsValid($config)) {
            throw new InvalidConfigException('Config given is invalid. Please check the structure.');
        }

        $collection = $this->buildConfigCollection($config);

        $oldCollection = $this->getCollectionFromDB();

        $this->cleanConfig();

        try {
            $this->saveCollection($collection);
        } catch (\Exception $e) {
            // If something goes wrong when saving config, we roll back the old config
            $this->cleanConfig();
            $this->saveCollection($oldCollection);

            throw new CannotSaveConfigException('Unable to save the config given.');
        }

        return $this->getCollectionFromDB();
    }

    private function assertConfigIsValid(array $config): bool
    {
        if (empty($config)) {
            return false;
        }

        foreach ($config as $singleConfig) {
            if (
                !isset($singleConfig['config_key']) ||
                !isset($singleConfig['config_value']) ||
                !isset($singleConfig['ps_version']) ||
                !isset($singleConfig['mbo_version'])
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $config
     *
     * @return Config[]
     *
     * @throws InvalidConfigException
     */
    private function buildConfigCollection(array $config): array
    {
        $collection = [];

        foreach ($config as $singleConfig) {
            $collection[] = new Config(
                $singleConfig['config_key'],
                $singleConfig['config_value'],
                $singleConfig['ps_version'],
                $singleConfig['mbo_version'],
                isset($singleConfig['applied']) ? (bool) $singleConfig['applied'] : false,
                isset($singleConfig['id_mbo_api_config']) ? (int) $singleConfig['id_mbo_api_config'] : null
            );
        }

        return $collection;
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws InvalidConfigException
     */
    public function getCollectionFromDB(): array
    {
        $collection = [];

        if (count(Db::getInstance()->executeS('SHOW TABLES LIKE \'' . _DB_PREFIX_ . 'mbo_api_config\' '))) { //check if table exist
            $query = 'SELECT
               `id_mbo_api_config`,
               `config_key`,
               `config_value`,
               `ps_version`,
               `mbo_version`,
               `applied`
            FROM ' . _DB_PREFIX_ . 'mbo_api_config';

            /** @var array $results */
            $results = $this->db->executeS($query);

            if (!is_array($results)) {
                throw new PrestaShopDatabaseException(sprintf('Retrieving config from DB returns a non array : %s. Query was : %s', gettype($results), $query));
            }

            $collection = $this->buildConfigCollection($results);
        }

        return $collection;
    }

    private function cleanConfig(): void
    {
        $sql = [];
        $sql[] = 'TRUNCATE TABLE `' . _DB_PREFIX_ . 'mbo_api_config`';

        foreach ($sql as $query) {
            if ($this->db->execute($query) == false) {
                throw new QueryException($this->db->getMsgError());
            }
        }
    }

    /**
     * @param Config[] $collection
     */
    private function saveCollection(array $collection)
    {
        if (empty($collection)) {
            return true;
        }

        $dateAdd = (new \DateTime())->format('Y-m-d H:i:s');
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'mbo_api_config`(`config_key`,`config_value`,`ps_version`,`mbo_version`,`applied`,`date_add`) VALUES ';
        /**
         * @var Config $config
         */
        foreach ($collection as $config) {
            $sql .= sprintf(
                "('%s', '%s', '%s', '%s', '%d', '%s'),",
                $config->getConfigKey(),
                $config->getConfigValue(),
                $config->getPsVersion(),
                $config->getMboVersion(),
                $config->isApplied(),
                $dateAdd
            );
        }

        $sql = rtrim($sql, ',') . ';';

        return $this->db->execute($sql);
    }
}
