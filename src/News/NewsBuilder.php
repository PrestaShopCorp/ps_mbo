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

use Context;
use PrestaShop\PrestaShop\Adapter\Tools;

class NewsBuilder
{
    /**
     * @var string[]
     */
    private $analyticParams;

    /**
     * @var Tools
     */
    private $tools;

    public function __construct(Tools $tools)
    {
        $this->tools = $tools;
    }

    public function build(
        \Datetime $date,
        string $title,
        string $description,
        string $link,
        string $countryIsoCode,
        int $contextMode
    ): News {
        $analyticParams = $this->getAnalyticsParams($countryIsoCode, $contextMode);

        return new News(
            $this->formatDate($date),
            $this->formatTitle($title),
            $this->formatDescription($description),
            $this->buildLink($link, $analyticParams)
        );
    }

    /**
     * @param \Datetime $date
     *
     * @return string
     */
    private function formatDate(\Datetime $date): string
    {
        try {
            return $this->tools->displayDate($date->format('Y-m-d H:i:s'));
        } catch (\Exception $e) {
            return '';
        }
    }

    private function formatTitle(string $title): string
    {
        return mb_convert_encoding(htmlentities($title, ENT_QUOTES), 'UTF-8', 'HTML-ENTITIES');
    }

    private function formatDescription(string $description)
    {
        return $this->tools->truncateString(strip_tags($description), 150);
    }

    private function buildLink(string $link, array $analyticParams): string
    {
        $url_query = parse_url($link, PHP_URL_QUERY) ?? '';
        parse_str($url_query, $link_query_params);
        $full_url_params = array_merge($link_query_params, $analyticParams);
        $base_url = explode('?', $link);
        $base_url = (string) $base_url[0];

        return $base_url . '?' . http_build_query($full_url_params);
    }

    protected function getAnalyticsParams(string $countryIsoCode, int $contextMode): array
    {
        if (null !== $this->analyticParams) {
            return $this->analyticParams;
        }

        $this->analyticParams = [
            'utm_source' => 'back-office',
            'utm_medium' => 'rss',
            'utm_campaign' => 'back-office-' . $countryIsoCode,
            'utm_content' => $this->isHostContext($contextMode) ? 'cloud' : 'download',
        ];

        return $this->analyticParams;
    }

    protected function isHostContext(int $contextMode): bool
    {
        return in_array($contextMode, [Context::MODE_HOST, Context::MODE_HOST_CONTRIB]);
    }
}
