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

namespace PrestaShop\Module\Mbo\Tab;

use Closure;
use Doctrine\Common\Cache\CacheProvider;
use PrestaShop\CircuitBreaker\Contract\FactoryInterface;
use PrestaShop\CircuitBreaker\FactorySettings;
use PrestaShop\CircuitBreaker\SimpleCircuitBreakerFactory;
use PrestaShop\Module\Mbo\Adapter\TabCollectionDecoderXml;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class TabCollectionProvider implements TabCollectionProviderInterface
{
    const CACHE_KEY = 'recommendedModules';

    const CACHE_LIFETIME_SECONDS = 604800; // 7 days same as defined in Core

    const API_URL = 'https://api.prestashop.com/xml/tab_modules_list_17.xmllllll';

    const API_ALLOWED_FAILURES = 2;

    const API_TIMEOUT_SECONDS = 0.6;

    const API_THRESHOLD_SECONDS = 3600; // Retry in 1 hour

    /**
     * @var TabCollectionFactoryInterface
     */
    private $tabCollectionFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

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
     * @param TabCollectionFactoryInterface $tabCollectionFactory
     * @param LoggerInterface $logger
     * @param CacheProvider|null $cacheProvider
     */
    public function __construct(
        TabCollectionFactoryInterface $tabCollectionFactory,
        LoggerInterface $logger,
        CacheProvider $cacheProvider = null
    ) {
        $this->tabCollectionFactory = $tabCollectionFactory;
        $this->logger = $logger;
        $this->cacheProvider = $cacheProvider;

        $this->apiSettings = new FactorySettings(
            self::API_ALLOWED_FAILURES,
            self::API_TIMEOUT_SECONDS,
            self::API_THRESHOLD_SECONDS
        );

        $this->apiSettings
            ->setClientOptions([
                'method' => 'GET',
            ])
        ;

        $this->circuitBreakerFactory = new SimpleCircuitBreakerFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function getTab($tabClassName)
    {
        $tabCollection = $this->getTabCollection();

        return $tabCollection->getTab($tabClassName);
    }

    /**
     * {@inheritdoc}
     */
    public function getTabCollection()
    {
        if ($this->isTabCollectionCached()) {
            return $this->cacheProvider->fetch(static::CACHE_KEY);
        }

        $tabCollection = $this->getTabCollectionFromApi();

        if ($this->cacheProvider
            && !$tabCollection->isEmpty()
        ) {
            $this->cacheProvider->save(
                static::CACHE_KEY,
                $tabCollection,
                static::CACHE_LIFETIME_SECONDS
            );
        }

        return $tabCollection;
    }

    /**
     * Check if recommended modules cache is set
     *
     * @return bool
     */
    public function isTabCollectionCached()
    {
        return $this->cacheProvider
            && $this->cacheProvider->contains(static::CACHE_KEY);
    }

    /**
     * Retrieve tabs with recommended modules from PrestaShop
     *
     * @return TabCollectionInterface
     *
     * @throws ServiceUnavailableHttpException
     */
    private function getTabCollectionFromApi()
    {
        $circuitBreaker = $this->circuitBreakerFactory->create($this->apiSettings);

        $apiResponse = $circuitBreaker->call(
            self::API_URL,
            [],
            $this->circuitBreakerFallback()
        );

        $tabCollectionDecoderXml = new TabCollectionDecoderXml($apiResponse);

        $tabCollection = $this->tabCollectionFactory->buildFromArray($tabCollectionDecoderXml->toArray());

        return $tabCollection;
    }

    /**
     * Called by CircuitBreaker if the service is unavailable
     *
     * @return Closure
     */
    private function circuitBreakerFallback()
    {
        return function() {
            throw new ServiceUnavailableHttpException(self::API_THRESHOLD_SECONDS);
        };
    }
}
