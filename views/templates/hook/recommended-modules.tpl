{**
 * 2007-2019 PrestaShop SA and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}

<script>
  var mboConfiguration = {
    translations: {
      'Recommended Modules and Services': '{$recommendedModulesTitle|escape:'javascript'}',
    },
    recommendedModulesUrl: '{$recommendedModulesUrl|escape:'javascript'}',
    shouldAttachRecommendedModulesAfterContent: {$shouldAttachRecommendedModulesAfterContent|intval},
    shouldAttachRecommendedModulesButton: {$shouldAttachRecommendedModulesButton|intval},
    shouldUseLegacyTheme: {$shouldUseLegacyTheme|intval},
  };

  mbo.initialize(mboConfiguration);
</script>
