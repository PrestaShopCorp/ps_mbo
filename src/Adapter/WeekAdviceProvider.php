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

namespace PrestaShop\Module\Mbo\Adapter;

use Doctrine\Common\Cache\CacheProvider;
use PrestaShop\Module\Mbo\Core\ExternalContentProvider\ExternalContentProviderInterface;
use PrestaShop\Module\Mbo\Core\WeekAdvice\WeekAdvice;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Serializer\SerializerInterface;

class WeekAdviceProvider
{
    const CACHE_KEY = 'mboWeekAdvice';

    const CACHE_LIFETIME_SECONDS = 86400; // 24 hours same as defined in psaddonsconnect

    const API_URL = 'https://api-addons.prestashop.com';

    /**
     * @var ExternalContentProviderInterface
     */
    private $externalContentProvider;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var CacheProvider
     */
    private $cacheProvider;

    /**
     * @var LegacyContext
     */
    private $context;

    /**
     * @var string
     */
    private $cacheKey;

    /**
     * Constructor.
     *
     * @param SerializerInterface $serializer
     * @param CacheProvider $cacheProvider
     */
    public function __construct(
        SerializerInterface $serializer,
        CacheProvider $cacheProvider,
        LegacyContext $context
    ) {
        $this->serializer = $serializer;
        $this->cacheProvider = $cacheProvider;
        $this->context = $context;
        $this->cacheKey = self::CACHE_KEY . '-' . $this->context->getContext()->language->iso_code;
        $this->externalContentProvider = new ExternalContentProvider();
    }

    /**
     * @return WeekAdvice
     *
     * @throws ServiceUnavailableHttpException
     */
    public function getWeekAdvice()
    {
        if ($this->isCached()) {
            return $this->cacheProvider->fetch($this->cacheKey);
        }

        $apiResponse = $this->externalContentProvider->getContent(
            self::API_URL
            . '/request/?'
            . http_build_query([
                'method' => 'week_advice',
                'iso_lang' => $this->context->getContext()->language->iso_code,
            ])
        );

        $weekAdvice = $this->serializer->deserialize(
            $apiResponse,
            WeekAdvice::class,
            'json'
        );

        $this->cacheProvider->save(
            $this->cacheKey,
            $weekAdvice,
            static::CACHE_LIFETIME_SECONDS
        );

        return $weekAdvice;
    }

    /**
     * @return bool
     */
    public function isCached()
    {
        return $this->cacheProvider->contains($this->cacheKey);
    }
}
