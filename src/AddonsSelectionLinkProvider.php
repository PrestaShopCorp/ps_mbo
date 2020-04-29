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

use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShop\PrestaShop\Core\Foundation\Version;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Responsible of provide Addons url with Context params for Module Selection and Theme Catalog pages
 */
class AddonsSelectionLinkProvider
{
    /**
     * @var Version
     */
    private $version;

    /**
     * @var LegacyContext
     */
    private $context;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var RequestStack
     */
    private $requestStack;

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
    public function getLinkUrl()
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
}
