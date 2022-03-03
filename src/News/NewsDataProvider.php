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

namespace PrestaShop\Module\Mbo\News;

use PrestaShop\CircuitBreaker\Contract\CircuitBreakerInterface;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Adapter\Country\CountryDataProvider;
use PrestaShop\PrestaShop\Adapter\Validate;
use stdClass;

/**
 * Provide the news from https://www.prestashop.com/blog/
 */
class NewsDataProvider
{
    public const NUM_ARTICLES = 2;

    public const CLOSED_ALLOWED_FAILURES = 3;
    public const CLOSED_TIMEOUT_SECONDS = 3;

    public const OPEN_ALLOWED_FAILURES = 3;
    public const OPEN_TIMEOUT_SECONDS = 3;
    public const OPEN_THRESHOLD_SECONDS = 86400; // 24 hours

    /**
     * @var CircuitBreakerInterface
     */
    private $circuitBreaker;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var int
     */
    private $contextMode;

    /**
     * @var CountryDataProvider
     */
    private $countryDataProvider;

    /**
     * @var Validate
     */
    private $validate;

    /**
     * @var NewsBuilder
     */
    private $newsBuilder;

    /**
     * NewsDataProvider constructor.
     *
     * @param CircuitBreakerInterface $circuitBreaker
     * @param CountryDataProvider $countryDataProvider
     * @param Configuration $configuration
     * @param Validate $validate
     * @param int $contextMode
     */
    public function __construct(
        CircuitBreakerInterface $circuitBreaker,
        NewsBuilder $newsBuilder,
        CountryDataProvider $countryDataProvider,
        Configuration $configuration,
        Validate $validate,
        int $contextMode
    ) {
        $this->circuitBreaker = $circuitBreaker;
        $this->newsBuilder = $newsBuilder;
        $this->configuration = $configuration;
        $this->contextMode = $contextMode;
        $this->countryDataProvider = $countryDataProvider;
        $this->validate = $validate;
    }

    /**
     * @param string $isoCode
     *
     * @return array
     */
    public function getData(string $isoCode): array
    {
        $data = ['has_errors' => true, 'rss' => []];
        $apiUrl = $this->configuration->get('_PS_API_URL_');

        $blogXMLResponse = $this->circuitBreaker->call($apiUrl . '/rss/blog/blog-' . $isoCode . '.xml');

        if (empty($blogXMLResponse)) {
            $data['has_errors'] = false;

            return $data;
        }

        $rss = @simplexml_load_string($blogXMLResponse);
        if (!$rss) {
            return $data;
        }

        $articles_limit = self::NUM_ARTICLES;

        $newsCollection = new NewsCollection();

        $shopDefaultCountryId = (int) $this->configuration->get('PS_COUNTRY_DEFAULT');
        $shopDefaultCountryIsoCode = mb_strtoupper($this->countryDataProvider->getIsoCodebyId($shopDefaultCountryId), 'utf-8');

        /** @var stdClass $item */
        foreach ($rss->channel->item as $item) {
            if ($articles_limit === 0) {
                break;
            }
            if (!$this->validate->isCleanHtml((string) $item->title)
                || !$this->validate->isCleanHtml((string) $item->description)
                || empty($item->link)
                || empty($item->title)) {
                continue;
            }

            $newsCollection->addNews(
                $this->newsBuilder->build(
                    (string) $item->pubDate,
                    (string) $item->title,
                    (string) $item->description,
                    (string) $item->link,
                    $shopDefaultCountryIsoCode,
                    $this->contextMode
                )
            );
            --$articles_limit;
        }
        $data['has_errors'] = false;
        $data['rss'] = $newsCollection->toArray();

        return $data;
    }
}
