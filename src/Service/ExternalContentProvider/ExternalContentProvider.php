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

namespace PrestaShop\Module\Mbo\Service\ExternalContentProvider;

use Closure;
use PrestaShop\CircuitBreaker\FactorySettings;
use PrestaShop\CircuitBreaker\SimpleCircuitBreakerFactory;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Gateway to retrieve content from external source.
 */
class ExternalContentProvider implements ExternalContentProviderInterface
{
    const ALLOWED_FAILURES = 2;

    const TIMEOUT_SECONDS = 0.6;

    const THRESHOLD_SECONDS = 3600; // Retry in 1 hour

    /**
     * @var SimpleCircuitBreakerFactory
     */
    protected $circuitBreakerFactory;

    /**
     * @var OptionsResolver
     */
    protected $optionsResolver;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->circuitBreakerFactory = new SimpleCircuitBreakerFactory();
        $this->optionsResolver = new OptionsResolver();
        $this->configureOptions();
    }

    /**
     * {@inheritdoc}
     *
     * @throws ServiceUnavailableHttpException
     */
    public function getContent(string $url, array $options = []): string
    {
        $settings = $this->optionsResolver->resolve($options);

        $apiSettings = new FactorySettings(
            $settings['failures'],
            $settings['timeout'],
            $settings['threshold']
        );

        $circuitBreaker = $this->circuitBreakerFactory->create($apiSettings);

        return $circuitBreaker->call(
            $url,
            $settings['client_options'],
            $this->circuitBreakerFallback()
        );
    }

    protected function configureOptions(): void
    {
        $this->optionsResolver->setDefaults([
            'failures' => self::ALLOWED_FAILURES,
            'timeout' => self::TIMEOUT_SECONDS,
            'threshold' => self::THRESHOLD_SECONDS,
            'client_options' => [],
        ]);
        $this->optionsResolver->setAllowedTypes('failures', 'numeric');
        $this->optionsResolver->setAllowedTypes('timeout', 'numeric');
        $this->optionsResolver->setAllowedTypes('threshold', 'numeric');
        $this->optionsResolver->setAllowedTypes('client_options', 'array');
    }

    /**
     * Called by CircuitBreaker if the service is unavailable
     *
     * @return Closure
     */
    protected function circuitBreakerFallback(): Closure
    {
        return function () {
            throw new ServiceUnavailableHttpException(self::THRESHOLD_SECONDS);
        };
    }
}
