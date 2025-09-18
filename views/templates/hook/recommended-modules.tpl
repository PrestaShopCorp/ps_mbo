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
<script async type="application/javascript" src="{$cdc_error_templating_url}"></script>

{if $cdc_script_not_found}
  <script async type="application/javascript" src="{$cdc_error_url}"></script>
{else}
  <script async type="application/javascript" src="{$cdc_url}"></script>
{/if}
<script defer type="application/javascript" src="{$recommended_modules_js}"></script>
<link rel="stylesheet" href="{$recommended_modules_css}" type="text/css" media="all">

<script>
  window.$(document).ready(function () {
    if (undefined !== mbo) {
      mbo.initialize({
        translations: {
          'Recommended Modules and Services': '{$recommendedModulesTitleTranslated|escape:'javascript':'UTF-8'}',
          'Close': '{$recommendedModulesCloseTranslated|escape:'javascript':'UTF-8'}',
        },
        recommendedModulesUrl: '{$recommendedModulesUrl|escape:'javascript':'UTF-8'}',
        shouldAttachRecommendedModulesAfterContent: {$shouldAttachRecommendedModulesAfterContent|intval},
        shouldAttachRecommendedModulesButton: {$shouldAttachRecommendedModulesButton|intval},
        shouldUseLegacyTheme: {$shouldUseLegacyTheme|intval},
      });
    }
  });
</script>
