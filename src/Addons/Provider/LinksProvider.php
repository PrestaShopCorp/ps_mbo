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

namespace PrestaShop\Module\Mbo\Addons\Provider;

use Language;
use PrestaShop\Module\Mbo\Addons\PracticalLinks;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShop\PrestaShop\Core\Foundation\Version;
use Symfony\Component\HttpFoundation\RequestStack;

class LinksProvider
{
    const ADDONS_LANGUAGES = ['de', 'en', 'es', 'fr', 'it', 'nl', 'pl', 'pt', 'ru'];
    const DEFAULT_LANGUAGE = 'en';

    /**
     * @var Version
     */
    protected $version;

    /**
     * @var LegacyContext
     */
    protected $context;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param Version $version
     * @param LegacyContext $context
     * @param Configuration $configuration
     * @param RequestStack $requestStack
     */
    public function __construct(
        Version $version,
        LegacyContext $context,
        Configuration $configuration,
        RequestStack $requestStack
    ) {
        $this->version = $version;
        $this->context = $context;
        $this->configuration = $configuration;
        $this->requestStack = $requestStack;
    }

    /**
     * We cannot use http_build_query() here due to a bug on Addons
     *
     * @see https://github.com/PrestaShop/PrestaShop/pull/9255/files#r200498010
     *
     * @return string
     */
    public function getSelectionLink(): string
    {
        $link = 'https://addons.prestashop.com/iframe/search-1.7.php?psVersion=' . $this->version->getVersion()
            . '&isoLang=' . $this->context->getContext()->language->iso_code
            . '&isoCurrency=' . $this->context->getContext()->currency->iso_code
            . '&isoCountry=' . $this->context->getContext()->country->iso_code
            . '&activity=' . $this->configuration->getInt('PS_SHOP_ACTIVITY')
            . '&parentUrl=' . $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost();

        if ('AdminPsMboTheme' === $this->requestStack->getCurrentRequest()->attributes->get('_legacy_controller')) {
            $link .= '&onlyThemes=1';
        }

        return $link;
    }

    /**
     * @return string
     */
    public function getThemesLinkUrl(): string
    {
        $isoCode = strtoupper($this->context->getLanguage()->iso_code);
        $languageAddons = in_array(strtolower($isoCode), static::ADDONS_LANGUAGES) ? strtolower($isoCode) : static::DEFAULT_LANGUAGE;

        return sprintf(
            '%s?%s',
            'https://addons.prestashop.com/' . $languageAddons . '/3-templates-prestashop',
            http_build_query([
                'utm_source' => 'back-office',
                'utm_medium' => 'theme-button',
                'utm_campaign' => 'back-office-' . $isoCode,
                'utm_content' => 'download',
            ])
        );
    }

    public function getDashboardPracticalLinks(): array
    {
        $idLang = $this->context->language->id;
        $isoCode = Language::getIsoById($idLang);
        if (false === $isoCode) {
            $isoCode = self::DEFAULT_LANGUAGE;
        }

        $isoCode = mb_strtolower($isoCode);

        return PracticalLinks::getByIsoCode($isoCode);
    }
}
