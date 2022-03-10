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

namespace PrestaShop\Module\Mbo;

use PrestaShop\PrestaShop\Adapter\LegacyContext;

/**
 * Responsible for provide Addons url with Context params for theme recommendation
 */
class AddonsThemesLinkProvider
{
    const ADDONS_LANGUAGES = ['de', 'en', 'es', 'fr', 'it', 'nl', 'pl', 'pt', 'ru'];
    const DEFAULT_LANGUAGE = 'en';

    /**
     * @var LegacyContext
     */
    private $context;

    /**
     * @param LegacyContext $context
     */
    public function __construct(
        LegacyContext $context
    ) {
        $this->context = $context;
    }

    /**
     * @return string
     */
    public function getLinkUrl()
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
}
