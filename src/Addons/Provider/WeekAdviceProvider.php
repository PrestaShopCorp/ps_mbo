<?php

namespace PrestaShop\Module\Mbo\Addons\Provider;

use PrestaShop\CircuitBreaker\AdvancedCircuitBreakerFactory;
use PrestaShop\CircuitBreaker\Contract\FactoryInterface;
use PrestaShop\CircuitBreaker\Contract\FactorySettingsInterface;
use PrestaShop\CircuitBreaker\FactorySettings;
use PrestaShop\CircuitBreaker\Storage\DoctrineCache;
use PrestaShop\Module\Mbo\Addons\WeekAdvice;

class WeekAdviceProvider
{
    const CACHE_DURATION = 86400; //24 hours

    const ADDONS_API_URL = 'https://api-addons.prestashop.com';

    const CLOSED_ALLOWED_FAILURES = 2;
    const API_TIMEOUT_SECONDS = 0.6;

    const OPEN_ALLOWED_FAILURES = 1;
    const OPEN_TIMEOUT_SECONDS = 1.2;

    const OPEN_THRESHOLD_SECONDS = 3600;

    /** @var FactoryInterface */
    private $factory;

    /** @var FactorySettingsInterface */
    private $factorySettings;

    public function __construct(DoctrineCache $doctrineCache)
    {
        //Init circuit breaker factory
        $this->factorySettings = new FactorySettings(self::CLOSED_ALLOWED_FAILURES, self::API_TIMEOUT_SECONDS, self::OPEN_THRESHOLD_SECONDS);
        $this->factorySettings
            ->setStrippedFailures(self::OPEN_ALLOWED_FAILURES)
            ->setStrippedTimeout(self::OPEN_TIMEOUT_SECONDS)
            ->setStorage($doctrineCache)
            ->setClientOptions(['method' => 'GET'])
        ;
        $this->factory = new AdvancedCircuitBreakerFactory();
    }

    /**
     * @param string $isoCode
     *
     * @return WeekAdvice|null
     */
    public function getByIsoCode(string $isoCode): ?WeekAdvice
    {
        $circuitBreaker = $this->factory->create($this->factorySettings);
        $apiJsonResponse = $circuitBreaker->call(
            self::ADDONS_API_URL . '/request/?' . http_build_query(['method' => 'week_advice', 'iso_lang' => $isoCode])
        );

        $weekAdviceData = !empty($apiJsonResponse) ? json_decode($apiJsonResponse, true) : [];

        if (empty($weekAdviceData['advice'])) {
            return null;
        }

        $weekAdvice = new WeekAdvice((string) $weekAdviceData['advice']);

        if (!empty($weekAdviceData['link'])) {
            $weekAdvice->setLink(
                sprintf(
                    '%s?utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-%s&utm_content=tipofthemoment',
                    (string) $weekAdviceData['link'],
                    $isoCode
                )
            );
        }

        return $weekAdvice;
    }
}
