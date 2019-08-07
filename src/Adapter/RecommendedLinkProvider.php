<?php
/**
 * 2007-2019 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
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
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\Mbo\Adapter;

use PrestaShop\Module\Mbo\Core\RecommendedLink\RecommendedLink;
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
            . strtolower($this->context->getLanguage()->getIsoCode())
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
