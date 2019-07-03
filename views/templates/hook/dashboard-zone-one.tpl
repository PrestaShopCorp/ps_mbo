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

<section id="psaddonsconnect-widget-container" class="widget">
  <div class="panel">
    <div class="panel-heading">
        <i class="icon-puzzle-piece"></i>
        {l s='TIPS & UPDATES' mod='psaddonsconnect'}
        <div class="panel-heading-action">
          <a href="#" class="list-toolbar-btn" id="psaddonsconnect-widget-close">
            <i class="process-icon-cancel"></i>
          </a>
        </div>
    </div>
    {if $shouldDisplayAddonsLogin}
    <div id="psaddonsconnect-addons-login-container">
      <p>{l s='Connect to your account right now to enjoy updates (security and features) on all of your modules.' mod='psaddonsconnect'}</p>
      <div class="text-center">
        <button type="button" id="psaddonsconnect-addons-login-button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modal_addons_connect">
          {l s='CONNECT TO PRESTASHOP MARKETPLACE' mod='psaddonsconnect'}
        </button>
      </div>
    </div>
    {/if}
    <div class="clearfix">
      <h4>{l s='Tip of the moment' mod='psaddonsconnect'}</h4>
      <div id="psaddonsconnect-tips-loader-container"{if $weekAdvice} style="display: none;"{/if}>
        <i class="icon-refresh icon-spin"></i>
      </div>
      <div id="psaddonsconnect-tips-content-container">
        {if $weekAdvice}
          <p><i class="icon-lightbulb"></i> {$weekAdvice->advice}</p>
          <a href="{$weekAdvice->link}" target="_blank" class="btn btn-default btn-sm pull-right">
            {$adviceLinkTranslated}
          </a>
        {/if}
      </div>
    </div>
    <h4>{l s='Practical links' mod='psaddonsconnect'}</h4>
    <div class="list-group">
      <a class="list-group-item" href="#">
        {l s='Modules to' mod='psaddonsconnect'} {l s='increase your traffic' mod='psaddonsconnect'}
      </a>
      <a class="list-group-item" href="#">
        {l s='Modules to' mod='psaddonsconnect'} {l s='boost your conversions' mod='psaddonsconnect'}
      </a>
      <a class="list-group-item" href="#">
        {l s='Modules to' mod='psaddonsconnect'} {l s='increase your clients\' average cart' mod='psaddonsconnect'}
      </a>
      <a class="list-group-item" href="#">
        {l s='Selection of modules recommended for' mod='psaddonsconnect'} {l s='your business sector' mod='psaddonsconnect'}
      </a>
    </div>
  </div>
</section>

<script>
  var mboDashboardWidgetConfiguration = {
    translations: {
      'See the entire selection': '{$adviceLinkTranslated|escape:'javascript'}',
      'No tip available today.': '{$adviceUnavailableTranslated|escape:'javascript'}',
    },
    weekAdviceUrl: '{$weekAdviceUrl|escape:'javascript'}',
  };

  mboDashboardWidget.initialize(mboDashboardWidgetConfiguration);
</script>
