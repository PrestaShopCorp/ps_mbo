<?php
/**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\Mbo\Tab;

use Doctrine\Common\Cache\CacheProvider;
use PrestaShop\Module\Mbo\ExternalContentProvider\ExternalContentProviderInterface;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class TabCollectionProvider implements TabCollectionProviderInterface
{
    const CACHE_KEY = 'recommendedModules';

    const CACHE_LIFETIME_SECONDS = 604800;

    const API_URL = 'https://api.prestashop.com/xml/tab_modules_list_17.xml';

    /**
     * @var LegacyContext
     */
    private $context;

    /**
     * @var ExternalContentProviderInterface
     */
    private $externalContentProvider;

    /**
     * @var TabCollectionFactoryInterface
     */
    private $tabCollectionFactory;

    /**
     * @var CacheProvider|null
     */
    private $cacheProvider;

    /**
     * @param LegacyContext $context
     * @param ExternalContentProviderInterface $externalContentProvider
     * @param TabCollectionFactoryInterface $tabCollectionFactory
     * @param CacheProvider|null $cacheProvider
     */
    public function __construct(
        LegacyContext $context,
        ExternalContentProviderInterface $externalContentProvider,
        TabCollectionFactoryInterface $tabCollectionFactory,
        CacheProvider $cacheProvider = null
    ) {
        $this->context = $context;
        $this->externalContentProvider = $externalContentProvider;
        $this->tabCollectionFactory = $tabCollectionFactory;
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getTabCollection()
    {
        if ($this->isTabCollectionCached()) {
            return $this->cacheProvider->fetch($this->getCacheKey());
        }

        $tabCollection = $this->getTabCollectionFromApi();

        if ($this->cacheProvider
            && false === $tabCollection->isEmpty()
        ) {
            $this->cacheProvider->save(
                $this->getCacheKey(),
                $tabCollection,
                static::CACHE_LIFETIME_SECONDS
            );
        }

        return $tabCollection;
    }

    private function getCacheKey()
    {
        return static::CACHE_KEY . '-' . $this->context->getEmployeeLanguageIso();
    }

    /**
     * Check if recommended modules cache is set
     *
     * @return bool
     */
    public function isTabCollectionCached()
    {
        return $this->cacheProvider
            && $this->cacheProvider->contains($this->getCacheKey());
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
        $apiResponse = $this->externalContentProvider->getContent(self::API_URL);
        $tabCollectionDecoderXml = new TabCollectionDecoderXml($apiResponse);

        return $this->tabCollectionFactory->buildFromArray($tabCollectionDecoderXml->toArray());
    }
}
