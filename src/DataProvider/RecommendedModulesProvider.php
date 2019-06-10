<?php
/**
 * 2007-2019 PrestaShop SA and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\Mbo\DataProvider;

use Doctrine\Common\Cache\CacheProvider;
use Psr\Log\LoggerInterface;

/**
 * Class RecommendedModulesProvider is responsible for providing recommended modules.
 */
class RecommendedModulesProvider
{
    const CACHE_KEY = 'recommendedModules';

    const CACHE_LIFETIME = 604800; // 7 days

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var CacheProvider
     */
    private $cacheProvider;

    /**
     * ModuleCatalogDataProvider constructor.
     *
     * @param LoggerInterface $logger
     * @param CacheProvider|null $cacheProvider
     */
    public function __construct(
        LoggerInterface $logger,
        CacheProvider $cacheProvider = null
    ) {
        $this->logger = $logger;
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * Get recommended modules.
     *
     * @return array
     */
    public function getRecommendedModules()
    {
        if ($this->isCached()) {
            return $this->cacheProvider->fetch(static::CACHE_KEY);
        }

        return $this->loadRecommendedModulesData();
    }

    /**
     * Retrieve recommended modules from PrestaShop
     * @todo implement CircuitBreaker and tool to parse XML and return an array
     *
     * @return array
     */
    private function loadRecommendedModulesData()
    {
        $recommendedModules = [];

        if ($recommendedModules) {
            $isCacheSaved = $this->setCache($recommendedModules);

            if (!$isCacheSaved) {
                $this->logger->error('Unable to save recommended modules into the cache.');
            }
        }

        return $recommendedModules;
    }

    /**
     * If cache exists, get recommended modules from the cache.
     *
     * @return array Recommended modules loaded from the cache
     */
    private function fallbackOnCache()
    {
        if ($this->isCached()) {
            return $this->cacheProvider->fetch(static::CACHE_KEY);
        }

        $this->logger->error('Unable to fallback on recommended modules cache.');

        return [];
    }

    /**
     * Check if cache exist.
     *
     * @return bool
     */
    private function isCached()
    {
        return $this->cacheProvider
            && $this->cacheProvider->contains(static::CACHE_KEY);
    }

    /**
     * Save recommended modules into the cache.
     *
     * @param array $recommendedModules
     *
     * @return bool
     */
    private function setCache(array $recommendedModules)
    {
        return $this->cacheProvider
            && $this->cacheProvider->save(
                static::CACHE_KEY,
                $recommendedModules,
                static::CACHE_LIFETIME
            );
    }

    /**
     * Clear cache if exist.
     *
     * @return bool
     */
    private function clearCache()
    {
        $isCacheCleared = true;

        if ($this->cacheProvider) {
            $isCacheCleared = $this->cacheProvider->delete(static::CACHE_KEY);

            if (!$isCacheCleared) {
                $this->logger->error('Unable to clear recommended modules cache.');
            }
        }

        return $isCacheCleared;
    }
}
