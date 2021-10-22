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

namespace PrestaShop\Module\Mbo\RecommendedLink;

use PrestaShop\PrestaShop\Adapter\LegacyContext;
use Symfony\Component\Serializer\SerializerInterface;

class RecommendedLinkProvider
{
    /**
     * @var LegacyContext
     */
    private $context;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Constructor.
     *
     * @param SerializerInterface $serializer
     * @param LegacyContext $context
     */
    public function __construct(
        LegacyContext $context,
        SerializerInterface $serializer
    ) {
        $this->context = $context;
        $this->serializer = $serializer;
    }

    /**
     * @return RecommendedLink[]
     */
    public function getRecommendedLinks()
    {
        $recommendedLinks = [];
        $cacheFile = $this->getCacheFile();

        if ($cacheFile) {
            $recommendedLinks = $this->serializer->deserialize(
                file_get_contents($cacheFile),
                sprintf('%s[]', RecommendedLink::class),
                'json'
            );
        }

        return $recommendedLinks;
    }

    /**
     * Search a cache associated to context language or try to fallback to default locale
     *
     * @return string
     */
    private function getCacheFile()
    {
        $cacheFile = _PS_MODULE_DIR_
            . 'ps_mbo/cache/recommended-links-'
            . strtolower($this->context->getContext()->language->iso_code)
            . '.json'
        ;

        if (file_exists($cacheFile)) {
            return $cacheFile;
        }

        $cacheFile = _PS_MODULE_DIR_
            . 'ps_mbo/cache/recommended-links-en.json'
        ;

        if (file_exists($cacheFile)) {
            return $cacheFile;
        }

        return '';
    }
}
