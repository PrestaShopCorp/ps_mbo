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

use Doctrine\Common\Cache\FilesystemCache;
use GuzzleHttp\Subscriber\Cache\CacheStorage;
use GuzzleHttp\Subscriber\Cache\CacheSubscriber;
use PrestaShop\CircuitBreaker\AdvancedCircuitBreakerFactory;
use PrestaShop\CircuitBreaker\Contract\FactoryInterface;
use PrestaShop\CircuitBreaker\FactorySettings;
use PrestaShop\CircuitBreaker\Storage\DoctrineCache;
use PrestaShop\Module\Mbo\Adapter\RecommendedModulesXMLParser;
use PrestaShop\Module\Mbo\Factory\TabsRecommendedModulesFactoryInterface;
use PrestaShop\Module\Mbo\TabsRecommendedModules\TabRecommendedModulesInterface;
use PrestaShop\Module\Mbo\TabsRecommendedModules\TabsRecommendedModulesInterface;

class RecommendedModulesProvider
{
    const CACHE_DIRECTORY = 'ps_mbo';

    const CACHE_KEY = 'recommendedModules';

    const CACHE_LIFETIME = 604800; // 7 days

    const API_URL = 'https://api.prestashop.com/xml/tab_modules_list_17.xml';

    const CLOSED_ALLOWED_FAILURES = 2;

    const API_TIMEOUT_SECONDS = 0.6;

    const OPEN_ALLOWED_FAILURES = 1;

    const OPEN_TIMEOUT_SECONDS = 1.2;

    const OPEN_THRESHOLD_SECONDS = 60;

    /**
     * @var TabsRecommendedModulesFactoryInterface
     */
    private $tabsRecommendedModulesFactory;

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var array
     */
    private $apiSettings;

    /**
     * Constructor.
     *
     * @param TabsRecommendedModulesFactoryInterface $tabsRecommendedModulesFactory
     */
    public function __construct(TabsRecommendedModulesFactoryInterface $tabsRecommendedModulesFactory)
    {
        $this->tabsRecommendedModulesFactory = $tabsRecommendedModulesFactory;

        //Doctrine cache used for Guzzle and CircuitBreaker storage
        $doctrineCache = new FilesystemCache(
            _PS_CACHE_DIR_
            . DIRECTORY_SEPARATOR
            . self::CACHE_DIRECTORY
        );

        //Init Guzzle cache
        $cacheStorage = new CacheStorage(
            $doctrineCache,
            self::CACHE_KEY,
            self::CACHE_LIFETIME
        );

        $cacheSubscriber = new CacheSubscriber(
            $cacheStorage,
            function () {
                return true;
            }
        );

        //Init circuit breaker factory
        $storage = new DoctrineCache($doctrineCache);

        $this->apiSettings = new FactorySettings(
            self::CLOSED_ALLOWED_FAILURES,
            self::API_TIMEOUT_SECONDS,
            0
        );

        $this->apiSettings
            ->setThreshold(self::OPEN_THRESHOLD_SECONDS)
            ->setStrippedFailures(self::OPEN_ALLOWED_FAILURES)
            ->setStrippedTimeout(self::OPEN_TIMEOUT_SECONDS)
            ->setStorage($storage)
            ->setClientOptions([
                'subscribers' => [$cacheSubscriber],
                'method' => 'GET',
            ])
        ;

        $this->factory = new AdvancedCircuitBreakerFactory();
    }

    /**
     * Get recommended modules by Tab class name.
     *
     * @param string $tabClassName
     *
     * @return TabRecommendedModulesInterface
     */
    public function getTabRecommendedModules($tabClassName)
    {
        $tabsRecommendedModules = $this->getTabsRecommendedModulesFromApi();

        return $tabsRecommendedModules->getTab($tabClassName);
    }

    /**
     * Retrieve tabs with recommended modules from PrestaShop
     *
     * @return TabsRecommendedModulesInterface
     */
    private function getTabsRecommendedModulesFromApi()
    {
        $circuitBreaker = $this->factory->create($this->apiSettings);

        $apiResponse = $circuitBreaker->call(self::API_URL);

        $recommendedModulesXMLParser = new RecommendedModulesXMLParser($apiResponse);

        $tabsRecommendedModules = $this->tabsRecommendedModulesFactory->buildFromArray($recommendedModulesXMLParser->toArray());

        return $tabsRecommendedModules;
    }
}
