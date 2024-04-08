{**
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
 *}

<script>
  if (undefined !== mbo) {
    mbo.initialize({
      translations: {
        'Recommended Modules and Services': '{$recommendedModulesTitleTranslated|escape:'javascript'}',
        'description': "{$recommendedModulesDescriptionTranslated|escape:'javascript'}",
        'Close': '{$recommendedModulesCloseTranslated|escape:'javascript'}',
      },
      recommendedModulesUrl: '{$recommendedModulesUrl|escape:'javascript'}',
      shouldAttachRecommendedModulesAfterContent: {$shouldAttachRecommendedModulesAfterContent|intval},
      shouldAttachRecommendedModulesButton: {$shouldAttachRecommendedModulesButton|intval},
      shouldUseLegacyTheme: {$shouldUseLegacyTheme|intval},
    });
  }
</script>

{if $shouldDisplayModuleManagerMessage}
<script>
$(document).ready( function () {
  if (typeof window.mboCdc !== undefined && typeof window.mboCdc !== "undefined") {
    const targetDiv = $('#main-div .content-div').first()

    const divModuleManagerMessage = document.createElement("div");
    divModuleManagerMessage.setAttribute("id", "module-manager-message-cdc-container");

    divModuleManagerMessage.classList.add('module-manager-message-wrapper');
    divModuleManagerMessage.classList.add('cdc-container');

    targetDiv.prepend(divModuleManagerMessage)
    const renderModulesManagerMessage = window.mboCdc.renderModulesManagerMessage

    const context = {$shopContext};

    renderModulesManagerMessage(context, '#module-manager-message-cdc-container')
  }
})
</script>
{/if}
