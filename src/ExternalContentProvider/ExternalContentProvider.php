<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

namespace PrestaShop\Module\Mbo\ExternalContentProvider;

use Closure;
use PrestaShop\CircuitBreaker\Contract\CircuitBreakerInterface;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class ExternalContentProvider implements ExternalContentProviderInterface
{
    const CLOSED_ALLOWED_FAILURES = 3;
    const CLOSED_TIMEOUT_SECONDS = 5;

    const OPEN_ALLOWED_FAILURES = 3;
    const OPEN_TIMEOUT_SECONDS = 10;
    const OPEN_THRESHOLD_SECONDS = 3600; // 1 hour

    const CACHE_DURATION = 86400; // 24 hours

    /**
     * @var CircuitBreakerInterface
     */
    private $circuitBreaker;

    /**
     * @param CircuitBreakerInterface $circuitBreaker
     */
    public function __construct(CircuitBreakerInterface $circuitBreaker)
    {
        $this->circuitBreaker = $circuitBreaker;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ServiceUnavailableHttpException
     */
    public function getContent($url, array $options = [])
    {
        return $this->circuitBreaker->call(
            $url,
            $options,
            $this->circuitBreakerFallback()
        );
    }

    /**
     * Called by CircuitBreaker if the service is unavailable
     *
     * @return Closure
     */
    private function circuitBreakerFallback()
    {
        return function () {
            throw new ServiceUnavailableHttpException(static::OPEN_THRESHOLD_SECONDS);
        };
    }
}
