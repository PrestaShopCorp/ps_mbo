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
use PrestaShop\CircuitBreaker\AdvancedCircuitBreakerFactory;
use PrestaShop\CircuitBreaker\Contract\FactoryInterface;
use PrestaShop\CircuitBreaker\FactorySettings;
use PrestaShop\Module\Mbo\Adapter\RecommendedModulesXMLParser;
use PrestaShop\Module\Mbo\Factory\TabsRecommendedModulesFactoryInterface;
use PrestaShop\Module\Mbo\TabsRecommendedModules\TabRecommendedModulesInterface;
use PrestaShop\Module\Mbo\TabsRecommendedModules\TabsRecommendedModulesInterface;

class RecommendedModulesProvider
{
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
     * @var CacheProvider|null
     */
    private $cacheProvider;

    /**
     * @var FactoryInterface
     */
    private $circuitBreakerFactory;

    /**
     * @var array
     */
    private $apiSettings;

    /**
     * Constructor.
     *
     * @param TabsRecommendedModulesFactoryInterface $tabsRecommendedModulesFactory
     * @param CacheProvider|null $cacheProvider
     */
    public function __construct(
        TabsRecommendedModulesFactoryInterface $tabsRecommendedModulesFactory,
        CacheProvider $cacheProvider = null
    ) {
        $this->tabsRecommendedModulesFactory = $tabsRecommendedModulesFactory;
        $this->cacheProvider = $cacheProvider;

        $this->apiSettings = new FactorySettings(
            self::CLOSED_ALLOWED_FAILURES,
            self::API_TIMEOUT_SECONDS,
            0
        );

        $this->apiSettings
            ->setThreshold(self::OPEN_THRESHOLD_SECONDS)
            ->setStrippedFailures(self::OPEN_ALLOWED_FAILURES)
            ->setStrippedTimeout(self::OPEN_TIMEOUT_SECONDS)
            ->setClientOptions([
                'method' => 'GET',
            ])
        ;

        $this->circuitBreakerFactory = new AdvancedCircuitBreakerFactory();
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
        $tabsRecommendedModules = $this->getTabsRecommendedModules();

        return $tabsRecommendedModules->getTab($tabClassName);
    }

    /**
     * @return TabsRecommendedModulesInterface
     */
    public function getTabsRecommendedModules()
    {
        if ($this->cacheProvider
            && $this->cacheProvider->contains(static::CACHE_KEY)
        ) {
            return $this->cacheProvider->fetch(static::CACHE_KEY);
        }

        $tabsRecommendedModules = $this->getTabsRecommendedModulesFromApi();

        if ($this->cacheProvider
            && !$tabsRecommendedModules->isEmpty()
        ) {
            $this->cacheProvider->save(
                static::CACHE_KEY,
                $tabsRecommendedModules,
                static::CACHE_LIFETIME
            );
        }

        return $tabsRecommendedModules;
    }

    /**
     * Retrieve tabs with recommended modules from PrestaShop
     *
     * @return TabsRecommendedModulesInterface
     */
    private function getTabsRecommendedModulesFromApi()
    {
        $circuitBreaker = $this->circuitBreakerFactory->create($this->apiSettings);

        $apiResponse = $circuitBreaker->call(self::API_URL);

        $recommendedModulesXMLParser = new RecommendedModulesXMLParser($apiResponse);

        $tabsRecommendedModules = $this->tabsRecommendedModulesFactory->buildFromArray($recommendedModulesXMLParser->toArray());

        return $tabsRecommendedModules;
    }
}
