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
        $isoCode = $this->getIsoCode();

        return sprintf(
            '%s?%s',
            'https://addons.prestashop.com/' . mb_strtolower($isoCode) . '/3-templates-prestashop',
            http_build_query([
                'utm_source' => 'back-office',
                'utm_medium' => 'theme-button',
                'utm_campaign' => 'back-office-' . mb_strtoupper($isoCode),
                'utm_content' => 'download',
            ])
        );
    }

    public function getDashboardPracticalLinks(): array
    {
        $isoCode = mb_strtolower($this->getIsoCode());

        return PracticalLinks::getByIsoCode($isoCode);
    }

    public function getSignUpLink(): string
    {
        $isoCode = mb_strtolower($this->getIsoCode());

        return sprintf('https://auth.prestashop.com/%s/inscription', $isoCode)
            . sprintf('?lang=%s', $isoCode)
            . '&_ga=2.183749797.2029715227.1645605306-2047387021.1643627469'
            . '&_gac=1.81371877.1644238612.CjwKCAiAo4OQBhBBEiwA5KWu_5UzrywbBPo4PKIYESy7K-noavdo7Z4riOZMJEoM9mE1IE3gks0thxoCZOwQAvD_BwE';
    }

    public function getEmployeeMenuLinks(): array
    {
        $isoCode = mb_strtolower($this->getIsoCode());

        return [
            [
                'url' => 'https://www.prestashop.com'
                    . '/en' //should be language dependant, but not available yet
                    . '/resources/documentations'
                    . '?utm_source=back-office'
                    . '&utm_medium=profile'
                    . '&utm_campaign=resources-en' //should be language dependant, but not available yet
                    . '&utm_content=download17',
                'icon' => 'book',
                'label' => 'Resources',
            ],
            [
                'url' => 'https://www.prestashop.com'
                    . '/en' //should be language dependant, but not available yet
                    . '/training'
                    . '?utm_source=back-office'
                    . '&utm_medium=profile'
                    . '&utm_campaign=training-en' //should be language dependant, but not available yet
                    . '&utm_content=download17',
                'icon' => 'school',
                'label' => 'Training',
            ],
            [
                'url' => 'https://www.prestashop.com'
                    . sprintf('/%s', $isoCode)
                    . '/experts'
                    . '?utm_source=back-office'
                    . '&utm_medium=profile'
                    . sprintf('&utm_campaign=expert-%s', $isoCode)
                    . '&utm_content=download17',
                'icon' => 'person_pin_circle',
                'label' => 'Find an Expert',
            ],
            [
                'url' => 'https://addons.prestashop.com'
                    . sprintf('/%s/', $isoCode)
                    . '?utm_source=back-office'
                    . '&utm_medium=profile'
                    . sprintf('&utm_campaign=addons-%s', $isoCode)
                    . '&utm_content=download17',
                'icon' => 'extension',
                'label' => 'PrestaShop Marketplace',
            ],
            [
                'url' => 'https://www.prestashop.com'
                    . sprintf('/%s', $isoCode)
                    . '/contact'
                    . '?utm_source=back-office'
                    . '&utm_medium=profile'
                    . sprintf('&utm_campaign=help-center-%s', $isoCode)
                    . '&utm_content=download17',
                'icon' => 'help',
                'label' => 'Help Center',
            ],
        ];
    }

    private function getIsoCode(): string
    {
        $idLang = $this->context->getLanguage()->id;
        $isoCode = Language::getIsoById($idLang);
        if (false === $isoCode) {
            $isoCode = self::DEFAULT_LANGUAGE;
        }

        return $isoCode;
    }
}
