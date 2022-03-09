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
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShop\PrestaShop\Core\Foundation\Version;
use Symfony\Component\HttpFoundation\RequestStack;

class StaticLinksProvider
{
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

    public function getDashboardPracticalLinks()
    {
        $id_lang = $this->context->language->id;
        $iso_lang = Language::getIsoById($id_lang);

        $url = [
            'fr' => [
                'traffic' => 'https://addons.prestashop.com/fr/2-modules-prestashop?m=1&benefits=6&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-FR&utm_content=traffic',
                'conversion' => 'https://addons.prestashop.com/fr/2-modules-prestashop?m=1&benefits=1&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-FR&utm_content=conversions',
                'averageCart' => 'https://addons.prestashop.com/fr/2-modules-prestashop?m=1&benefits=3&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-FR&utm_content=cart',
                'businessSector' => 'https://addons.prestashop.com/fr/content/44-ressources-prestashop-les-outils-pour-reussir?utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-FR&utm_content=sector#modulebusinesssector',
            ],
            'en' => [
                'traffic' => 'https://addons.prestashop.com/en/2-modules-prestashop?m=1&benefits=6&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-EN&utm_content=traffic',
                'conversion' => 'https://addons.prestashop.com/en/2-modules-prestashop?m=1&benefits=1&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-EN&utm_content=conversions',
                'averageCart' => 'https://addons.prestashop.com/fr/2-modules-prestashop?m=1&benefits=3&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-FR&utm_content=cart',
                'businessSector' => 'https://addons.prestashop.com/fr/content/44-ressources-prestashop-les-outils-pour-reussir?utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-FR&utm_content=sector#modulebusinesssector',
            ],
            'es' => [
                'traffic' => 'https://addons.prestashop.com/es/2-modulos?m=1&benefits=6&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-ES&utm_content=traffic',
                'conversion' => 'https://addons.prestashop.com/es/2-modulos?m=1&benefits=1&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-ES&utm_content=conversions',
                'averageCart' => 'https://addons.prestashop.com/es/2-modulos?m=1&benefits=3&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-ES&utm_content=cart',
                'businessSector' => 'https://addons.prestashop.com/es/content/44-recursos-de-prestashop-herramientas-para-triunfar?utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-ES&utm_content=sector#modulebusinesssector',
            ],
            'de' => [
                'traffic' => 'https://addons.prestashop.com/de/2-modules-prestashop?m=1&benefits=6&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-DE&utm_content=traffic',
                'conversion' => 'https://addons.prestashop.com/de/2-modules-prestashop?m=1&benefits=1&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-DE&utm_content=conversions',
                'averageCart' => 'https://addons.prestashop.com/de/2-modules-prestashop?m=1&benefits=3&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-DE&utm_content=cart',
                'businessSector' => 'https://addons.prestashop.com/de/content/44-die-ressourcen-von-prestashop-die-tools-zu-ihrem-erfolg?utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-DE&utm_content=sector#modulebusinesssector',
            ],
            'it' => [
                'traffic' => 'https://addons.prestashop.com/it/2-modules-prestashop?m=1&benefits=6&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-IT&utm_content=traffic',
                'conversion' => 'https://addons.prestashop.com/it/2-modules-prestashop?m=1&benefits=1&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-IT&utm_content=conversions',
                'averageCart' => 'https://addons.prestashop.com/it/2-modules-prestashop?m=1&benefits=3&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-IT&utm_content=cart',
                'businessSector' => 'https://addons.prestashop.com/it/content/44-risorse-prestashop-gli-strumenti-per-avere-successo?utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-IT&utm_content=sector#modulebusinesssector',
            ],
            'nl' => [
                'traffic' => 'https://addons.prestashop.com/nl/2-modules-prestashop?m=1&benefits=6&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-NL&utm_content=traffic',
                'conversion' => 'https://addons.prestashop.com/nl/2-modules-prestashop?m=1&benefits=1&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-NL&utm_content=conversions',
                'averageCart' => 'https://addons.prestashop.com/nl/2-modules-prestashop?m=1&benefits=3&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-NL&utm_content=cart',
                'businessSector' => 'https://addons.prestashop.com/nl/content/44-prestashop-hulpmiddelen-de-tools-voor-een-succesvolle-webshop?utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-NL&utm_content=sector#modulebusinesssector',
            ],
            'pl' => [
                'traffic' => 'https://addons.prestashop.com/pl/2-moduly-prestashop?m=1&benefits=6&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-PL&utm_content=traffic',
                'conversion' => 'https://addons.prestashop.com/pl/2-2-moduly-prestashop?m=1&benefits=1&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-PL&utm_content=conversions',
                'averageCart' => 'https://addons.prestashop.com/pl/2-moduly-prestashop?m=1&benefits=3&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-PL&utm_content=cart',
                'businessSector' => 'https://addons.prestashop.com/pl/content/44-zasoby-prestashop-klucze-do-sukcesu?utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-PL&utm_content=sector#modulebusinesssector',
            ],
            'pt' => [
                'traffic' => 'https://addons.prestashop.com/pt/2-modules-prestashop?m=1&benefits=6&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-PT&utm_content=traffic',
                'conversion' => 'https://addons.prestashop.com/pt/2-modules-prestashop?m=1&benefits=1&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-PT&utm_content=conversions',
                'averageCart' => 'https://addons.prestashop.com/pt/2-modules-prestashop?m=1&benefits=3&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-PT&utm_content=cart',
                'businessSector' => 'https://addons.prestashop.com/pt/content/44-recursos-da-prestashop-as-ferramentas-para-o-seu-sucesso?utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-PT&utm_content=sector#modulebusinesssector',
            ],
            'ru' => [
                'traffic' => 'https://addons.prestashop.com/ru/2-modules-prestashop?m=1&benefits=6&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-RU&utm_content=traffic',
                'conversion' => 'https://addons.prestashop.com/ru/2-modules-prestashop?m=1&benefits=1&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-RU&utm_content=conversions',
                'averageCart' => 'https://addons.prestashop.com/ru/2-modules-prestashop?m=1&benefits=3&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-RU&utm_content=cart',
                'businessSector' => 'https://addons.prestashop.com/ru/content/44-prestashop-resources-the-tools-for-success?utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-RU&utm_content=sector#modulebusinesssector',
            ],
        ];

        if (!isset($url[$iso_lang])) {
            return $url['en'];
        }

        return $url[$iso_lang];
    }
}
