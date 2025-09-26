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

{if $cdc_script_not_found}
  <script defer type="application/javascript" src="{$cdc_error_url}"></script>
{else}
  <script defer type="application/javascript" src="{$cdc_url}"></script>
{/if}

{if isset($urlAccountsCdn)}
  <script src="{$urlAccountsCdn}" rel=preload></script>
  <script defer>
    var psAccountLoaded = false;
    if (window?.psaccountsVue) {
      window?.psaccountsVue?.init();
      psAccountLoaded = true;
    }
  </script>
{/if}

<script>
  window.$(document).ready(function () {
    window.mboDashboardContext = null;
    if (typeof window.mboCdc == undefined || typeof window.mboCdc == "undefined") {
      if (typeof renderCdcError === 'function') {
        renderCdcError($('#cdc-tips-and-update-container'));
      }
    } else {
      const renderTipsAndUpdate = window.mboCdc.renderDashboardTipsAndUpdate

      window.mboDashboardContext = {$shop_context};

      if (psAccountLoaded) {
        window.mboDashboardContext.accounts_component_loaded = true;
      }

      renderTipsAndUpdate(window.mboDashboardContext, '#cdc-tips-and-update-container')
    }
  });
</script>

<prestashop-accounts style="display: none;"></prestashop-accounts>

<section id="cdc-tips-and-update-container" class="cdc-container" data-error-path="{$cdcErrorUrl}"></section>
