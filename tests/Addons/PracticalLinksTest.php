<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

namespace PrestaShop\Module\Mbo\Tests\Addons;

use PHPUnit\Framework\TestCase;
use PrestaShop\Module\Mbo\Addons\PracticalLinks;
use PrestaShop\Module\Mbo\Addons\Provider\LinksProvider;

class PracticalLinksTest extends TestCase
{
    public function testGetByIsoCodeUnknownLang(): void
    {
        $unknownLangIsoCode = 'un';

        $defaultLangLinks = PracticalLinks::getByIsoCode(LinksProvider::DEFAULT_LANGUAGE);

        $this->assertSame($defaultLangLinks, PracticalLinks::getByIsoCode($unknownLangIsoCode));
    }

    /**
     * @dataProvider getIsoCodesAndLinks
     */
    public function testGetByIsoCode(string $isoCode, $linksByIsoCode): void
    {
        $this->assertSame($linksByIsoCode, PracticalLinks::getByIsoCode($isoCode));
    }

    public function getIsoCodesAndLinks(): \Generator
    {
        yield [
            'en',
            [
                'traffic' => 'https://addons.prestashop.com/en/2-modules-prestashop?m=1&benefits=6&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-EN&utm_content=traffic',
                'conversion' => 'https://addons.prestashop.com/en/2-modules-prestashop?m=1&benefits=1&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-EN&utm_content=conversions',
                'averageCart' => 'https://addons.prestashop.com/en/2-modules-prestashop?m=1&benefits=3&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-EN&utm_content=cart',
                'businessSector' => 'https://addons.prestashop.com/en/content/44-ressources-prestashop-les-outils-pour-reussir?utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-EN&utm_content=sector#modulebusinesssector',
            ],
        ];
        yield [
            'de',
            [
                'traffic' => 'https://addons.prestashop.com/de/2-modules-prestashop?m=1&benefits=6&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-DE&utm_content=traffic',
                'conversion' => 'https://addons.prestashop.com/de/2-modules-prestashop?m=1&benefits=1&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-DE&utm_content=conversions',
                'averageCart' => 'https://addons.prestashop.com/de/2-modules-prestashop?m=1&benefits=3&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-DE&utm_content=cart',
                'businessSector' => 'https://addons.prestashop.com/de/content/44-ressources-prestashop-les-outils-pour-reussir?utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-DE&utm_content=sector#modulebusinesssector',
            ],
        ];
        yield [
            'es',
            [
                'traffic' => 'https://addons.prestashop.com/es/2-modules-prestashop?m=1&benefits=6&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-ES&utm_content=traffic',
                'conversion' => 'https://addons.prestashop.com/es/2-modules-prestashop?m=1&benefits=1&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-ES&utm_content=conversions',
                'averageCart' => 'https://addons.prestashop.com/es/2-modules-prestashop?m=1&benefits=3&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-ES&utm_content=cart',
                'businessSector' => 'https://addons.prestashop.com/es/content/44-ressources-prestashop-les-outils-pour-reussir?utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-ES&utm_content=sector#modulebusinesssector',
            ],
        ];
        yield [
            'fr',
            [
                'traffic' => 'https://addons.prestashop.com/fr/2-modules-prestashop?m=1&benefits=6&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-FR&utm_content=traffic',
                'conversion' => 'https://addons.prestashop.com/fr/2-modules-prestashop?m=1&benefits=1&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-FR&utm_content=conversions',
                'averageCart' => 'https://addons.prestashop.com/fr/2-modules-prestashop?m=1&benefits=3&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-FR&utm_content=cart',
                'businessSector' => 'https://addons.prestashop.com/fr/content/44-ressources-prestashop-les-outils-pour-reussir?utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-FR&utm_content=sector#modulebusinesssector',
            ],
        ];
        yield [
            'it',
            [
                'traffic' => 'https://addons.prestashop.com/it/2-modules-prestashop?m=1&benefits=6&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-IT&utm_content=traffic',
                'conversion' => 'https://addons.prestashop.com/it/2-modules-prestashop?m=1&benefits=1&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-IT&utm_content=conversions',
                'averageCart' => 'https://addons.prestashop.com/it/2-modules-prestashop?m=1&benefits=3&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-IT&utm_content=cart',
                'businessSector' => 'https://addons.prestashop.com/it/content/44-ressources-prestashop-les-outils-pour-reussir?utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-IT&utm_content=sector#modulebusinesssector',
            ],
        ];
        yield [
            'nl',
            [
                'traffic' => 'https://addons.prestashop.com/nl/2-modules-prestashop?m=1&benefits=6&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-NL&utm_content=traffic',
                'conversion' => 'https://addons.prestashop.com/nl/2-modules-prestashop?m=1&benefits=1&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-NL&utm_content=conversions',
                'averageCart' => 'https://addons.prestashop.com/nl/2-modules-prestashop?m=1&benefits=3&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-NL&utm_content=cart',
                'businessSector' => 'https://addons.prestashop.com/nl/content/44-ressources-prestashop-les-outils-pour-reussir?utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-NL&utm_content=sector#modulebusinesssector',
            ],
        ];
        yield [
            'pl',
            [
                'traffic' => 'https://addons.prestashop.com/pl/2-modules-prestashop?m=1&benefits=6&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-PL&utm_content=traffic',
                'conversion' => 'https://addons.prestashop.com/pl/2-modules-prestashop?m=1&benefits=1&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-PL&utm_content=conversions',
                'averageCart' => 'https://addons.prestashop.com/pl/2-modules-prestashop?m=1&benefits=3&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-PL&utm_content=cart',
                'businessSector' => 'https://addons.prestashop.com/pl/content/44-ressources-prestashop-les-outils-pour-reussir?utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-PL&utm_content=sector#modulebusinesssector',
            ],
        ];
        yield [
            'pt',
            [
                'traffic' => 'https://addons.prestashop.com/pt/2-modules-prestashop?m=1&benefits=6&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-PT&utm_content=traffic',
                'conversion' => 'https://addons.prestashop.com/pt/2-modules-prestashop?m=1&benefits=1&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-PT&utm_content=conversions',
                'averageCart' => 'https://addons.prestashop.com/pt/2-modules-prestashop?m=1&benefits=3&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-PT&utm_content=cart',
                'businessSector' => 'https://addons.prestashop.com/pt/content/44-ressources-prestashop-les-outils-pour-reussir?utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-PT&utm_content=sector#modulebusinesssector',
            ],
        ];
        yield [
            'ru',
            [
                'traffic' => 'https://addons.prestashop.com/ru/2-modules-prestashop?m=1&benefits=6&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-RU&utm_content=traffic',
                'conversion' => 'https://addons.prestashop.com/ru/2-modules-prestashop?m=1&benefits=1&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-RU&utm_content=conversions',
                'averageCart' => 'https://addons.prestashop.com/ru/2-modules-prestashop?m=1&benefits=3&utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-RU&utm_content=cart',
                'businessSector' => 'https://addons.prestashop.com/ru/content/44-ressources-prestashop-les-outils-pour-reussir?utm_source=back-office&utm_medium=AddonsConnect&utm_campaign=back-office-RU&utm_content=sector#modulebusinesssector',
            ],
        ];
    }
}
