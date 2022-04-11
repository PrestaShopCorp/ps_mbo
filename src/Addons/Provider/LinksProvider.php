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
use Symfony\Contracts\Translation\TranslatorInterface;

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
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param Version $version
     * @param LegacyContext $context
     * @param Configuration $configuration
     * @param RequestStack $requestStack
     * @param TranslatorInterface $trans
     */
    public function __construct(
        Version $version,
        LegacyContext $context,
        Configuration $configuration,
        RequestStack $requestStack,
        TranslatorInterface $trans
    ) {
        $this->version = $version;
        $this->context = $context;
        $this->configuration = $configuration;
        $this->requestStack = $requestStack;
        $this->translator = $trans;
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

        return $this->translator->trans('https://addons.prestashop.com/en/3-templates-prestashop', [], 'Modules.Mbo.Links')
            . '?' . http_build_query([
                'utm_source' => 'back-office',
                'utm_medium' => 'theme-button',
                'utm_campaign' => 'back-office-' . mb_strtoupper($isoCode),
                'utm_content' => 'download',
            ]);
    }

    public function getDashboardPracticalLinks(): array
    {
        $isoCode = mb_strtolower($this->getIsoCode());

        return PracticalLinks::getByIsoCode($isoCode);
    }

    /**
     * @param string $controllerName
     * @param string $medium
     *
     * @return string|null
     */
    public function getAddonsLinkByControllerName(string $controllerName, string $medium): ?string
    {
        $utm = '?' . http_build_query([
           'utm_source' => 'back-office',
           'utm_medium' => $medium,
           'utm_content' => 'download',
           'utm_campaign' => 'back-office-' . $this->context->getLanguage()->iso_code,
        ]);
        switch ($controllerName) {
            case 'AdminCarriers':
                $link = $this->translator->trans('https://addons.prestashop.com/en/518-shipping-logistics', [], 'Modules.Mbo.Links');
                break;
            case 'AdminPayment':
                $link = $this->translator->trans('https://addons.prestashop.com/en/481-payment', [], 'Modules.Mbo.Links');
                break;
            case 'AdminController':
                $link = $this->translator->trans('https://addons.prestashop.com/en/209-dashboards', [], 'Modules.Mbo.Links');
                break;
            default:
                $link = $this->translator->trans('https://addons.prestashop.com/en/', [], 'Modules.Mbo.Links');
        }

        return $link . $utm;
    }

    public function getSignUpLink(): string
    {
        $isoCode = mb_strtolower($this->getIsoCode());

        return $this->translator->trans('https://auth.prestashop.com/en/login', [], 'Modules.Mbo.Links')
            . '?_ga=2.183749797.2029715227.1645605306-2047387021.1643627469'
            . '&_gac=1.81371877.1644238612.CjwKCAiAo4OQBhBBEiwA5KWu_5UzrywbBPo4PKIYESy7K-noavdo7Z4riOZMJEoM9mE1IE3gks0thxoCZOwQAvD_BwE';
    }

    public function getPasswordForgottenLink(): string
    {
        $passwordForgottenLinks = [
            'en' => 'https://auth.prestashop.com/en/password/request',
            'fr' => 'https://auth.prestashop.com/fr/mot-de-passe/demande-de-reinitialisation',
            'de' => 'https://auth.prestashop.com/de/passwort/anforderung',
            'es' => 'https://auth.prestashop.com/es/contrasena/solicitud',
            'it' => 'https://auth.prestashop.com/it/password/richiesta',
            'nl' => 'https://auth.prestashop.com/nl/wachtwoord/verzoek',
            'pl' => 'https://auth.prestashop.com/pl/haslo/zadanie',
            'pt' => 'https://auth.prestashop.com/pt/senha/solicite-uma-nova-senh',
            'ru' => 'https://auth.prestashop.com/ru/%D0%BF%D0%B0%D1%80%D0%BE%D0%BB%D1%8C/%D0%B7%D0%B0%D0%BF%D1%80%D0%BE%D1%81',
        ];

        $isoCode = mb_strtolower($this->getIsoCode());

        return array_key_exists($isoCode, $passwordForgottenLinks)
            ? $passwordForgottenLinks[$isoCode]
            : $passwordForgottenLinks[self::DEFAULT_LANGUAGE];
    }

    public function getEmployeeMenuLinks(): array
    {
        $isoCode = mb_strtolower($this->getIsoCode());
        $baseUtmString = '?' . http_build_query([
                'utm_source' => 'back-office',
                'utm_medium' => 'profile',
                'utm_content' => 'download17',
            ]);

        return [
            [
                'url' => $this->translator->trans('https://www.prestashop.com/en/resources', [], 'Modules.Mbo.Links')
                    . $baseUtmString
                    . sprintf('&utm_campaign=resources-%s', $isoCode),
                'icon' => 'book',
                'label' => $this->translator->trans('Resources', [], 'Modules.Mbo.Links'),
            ],
            [
                'url' => $this->translator->trans('https://www.prestashop.com/en/training', [], 'Modules.Mbo.Links')
                    . $baseUtmString
                    . sprintf('&utm_campaign=training-%s', $isoCode),
                'icon' => 'school',
                'label' => $this->translator->trans('Training', [], 'Modules.Mbo.Links'),
            ],
            [
                'url' => $this->translator->trans('https://www.prestashop.com/en/experts', [], 'Modules.Mbo.Links')
                    . $baseUtmString
                    . sprintf('&utm_campaign=expert-%s', $isoCode),
                'icon' => 'person_pin_circle',
                'label' => $this->translator->trans('Find an Expert', [], 'Modules.Mbo.Links'),
            ],
            [
                'url' => $this->translator->trans('https://addons.prestashop.com/en/', [], 'Modules.Mbo.Links')
                    . $baseUtmString
                    . sprintf('&utm_campaign=addons-%s', $isoCode),
                'icon' => 'extension',
                'label' => $this->translator->trans('PrestaShop Marketplace', [], 'Modules.Mbo.Links'),
            ],
            [
                'url' => $this->translator->trans('https://www.prestashop.com/en/contact', [], 'Modules.Mbo.Links')
                    . $baseUtmString
                    . sprintf('&utm_campaign=help-center-%s', $isoCode),
                'icon' => 'help',
                'label' => $this->translator->trans('Help Center', [], 'Modules.Mbo.Links'),
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
