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

namespace PrestaShop\Module\Mbo\Tab;

use Doctrine\Common\Cache\CacheProvider;
use PrestaShop\Module\Mbo\Service\ExternalContentProvider\ExternalContentProviderInterface;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Get tabs used to group modules from Addons
 */
class TabCollectionProvider implements TabCollectionProviderInterface
{
    public const CACHE_KEY = 'recommendedModules';

    public const CACHE_LIFETIME_SECONDS = 604800;

    public const API_URL = 'https://api.prestashop.com/xml/tab_modules_list_17.xml';

    /**
     * @var LegacyContext
     */
    protected $context;

    /**
     * @var ExternalContentProviderInterface
     */
    protected $externalContentProvider;

    /**
     * @var TabCollectionFactoryInterface
     */
    protected $tabCollectionFactory;

    /**
     * @var CacheProvider|null
     */
    protected $cacheProvider;

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
    public function getTabCollection(): TabCollectionInterface
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

    protected function getCacheKey(): string
    {
        return static::CACHE_KEY . '-' . $this->context->getEmployeeLanguageIso();
    }

    /**
     * Check if recommended modules cache is set
     *
     * @return bool
     */
    public function isTabCollectionCached(): bool
    {
        return $this->cacheProvider
            && $this->cacheProvider->contains($this->getCacheKey());
    }

    /**
     * @return void
     */
    public function clearCache(): void
    {
        if ($this->isTabCollectionCached()) {
            $this->cacheProvider->delete($this->getCacheKey());
        }
    }

    /**
     * Retrieve tabs with recommended modules from PrestaShop
     *
     * @return TabCollectionInterface
     *
     * @throws ServiceUnavailableHttpException
     */
    protected function getTabCollectionFromApi(): TabCollectionInterface
    {
        $apiResponse = $this->externalContentProvider->getContent(self::API_URL);
        $tabCollectionDecoderXml = new TabCollectionDecoderXml($apiResponse);

        return $this->tabCollectionFactory->buildFromArray($tabCollectionDecoderXml->toArray());
    }
}
