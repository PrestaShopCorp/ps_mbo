{**
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
 *}
<script defer type="application/javascript" src="{$cdc_error_templating_url}"></script>

{if isset($cdc_script_not_found) && $cdc_script_not_found}
  <script defer type="application/javascript" src="{$cdc_error_url}"></script>
{else}
  <script defer type="application/javascript" src="{$cdc_url}"></script>
{/if}

<script>
  window.$(document).ready(function () {

    $('#themes-logo-page > .card > .card-body > .row').append('<div id="cdc-explore-themes-catalog" class="col-lg-3 col-md-4 col-sm-6 theme-card-container cdc-container" data-error-path="{$cdcErrorUrl}"></div>')

    if (typeof window.mboCdc == undefined || typeof window.mboCdc == "undefined") {
      if (typeof renderCdcError === 'function') {
        renderCdcError($('#cdc-explore-themes-catalog'));
      }
    } else {
      const renderExploreThemesCatalog = window.mboCdc.renderExploreThemeCatalog

      const exploreThemesCatalogContext = {$shop_context};

      renderExploreThemesCatalog(exploreThemesCatalogContext, '#cdc-explore-themes-catalog')
    }
  });
</script>
