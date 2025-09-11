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
<script>
  window.$(document).ready(function () {
    if (typeof window.mboCdc == undefined || typeof window.mboCdc == "undefined") {
      if (typeof renderCdcError === 'function') {
        renderCdcError($('#cdc-dashboard-news'));
      }
    } else {
      const renderNews = window.mboCdc.renderDashboardNews
      if (!window.mboDashboardContext) {
        setTimeout(() => {
            if (window.mboDashboardContext) {
              renderNews(window.mboDashboardContext, '#cdc-dashboard-news')
            }
          },
          1000)
      } else {
        renderNews(window.mboDashboardContext, '#cdc-dashboard-news')
      }
    }
  });
</script>

<section id="cdc-dashboard-news" class="dash_news cdc-container" data-error-path="{$cdcErrorUrl}"></section>
