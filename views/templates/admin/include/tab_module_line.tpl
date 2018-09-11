{**
 * 2007-2018 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2018 PrestaShop SA
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}
<div class="row module-item-wrapper-list border-bottom mb-sm-3">
    <div class="col-12 col-sm-2 col-md-1 col-lg-1">
        <div class="module-logo-thumb-list text-center">
            <img alt="{$module->name}" src="{if isset($module->image)}../../../{$module->image}{else}{$smarty.const._MODULE_DIR_}{$module->name}/{$module->logo}{/if}" />
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-8 col-lg-9 pl-0">
        <p class="mb-0">
            <span style="display:none">{$module->name}</span>
            {$module->displayName}
            <span class="text-muted">v{$module->version} - by {$module->author}</span>
            {if isset($module->type) && $module->type == 'addonsBought'}
                - <span class="module-badge-bought help-tooltip text-warning" data-title="{l s="You bought this module on PrestaShop Addons. Thank You."}"><i class="icon-pushpin"></i> <small>{l s="Bought"}</small></span>
            {elseif isset($module->type) && $module->type == 'addonsMustHave'}
                - <span class="module-badge-popular help-tooltip text-primary" data-title="{l s="This module is available on PrestaShop Addons"}"><i class="icon-group"></i> <small>{l s="Popular"}</small></span>
            {elseif isset($module->type) && $module->type == 'addonsPartner'}
                - <span class="module-badge-partner help-tooltip text-warning" data-title="{l s="This module is available for free thanks to our partner."}"><i class="icon-pushpin"></i> <small>{l s="Official"}</small></span>
            {elseif isset($module->id) && $module->id gt 0}
                {if isset($module->version_addons) && $module->version_addons}
                    <span class="label label-warning">{l s='Need update'}</span>
                {/if}
            {/if}
        </p>
        <p class="text-muted">
            {if isset($module->description) && $module->description ne ''}
                {$module->description}
            {/if}
            {if isset($module->show_quick_view) &&  $module->show_quick_view}
                <br><a href="{if isset($admin_module_ajax_url_psmbo)}{$admin_module_ajax_url_psmbo}{/if}" class="controller-quick-view" data-name="{$module->name|escape:'html':'UTF-8'}"><i class="icon-search"></i> {l s='Read more'}</a>
            {/if}
        </p>
        {if isset($module->message) && (empty($module->name) !== false) && (!isset($module->type) || ($module->type != 'addonsMustHave' || $module->type !== 'addonsNative'))}<div class="alert alert-success"><button type="button" class="close" data-dismiss="alert">&times;</button>{$module->message}</div>{/if}
    </div>
    {if isset($module->type) && $module->type == 'addonsMustHave'}
        <div class="col-12 col-sm-4 col-md-3 col-lg-2 mb-3">
            <div class="text-center">
                <a href="{$module->addons_buy_url|replace:' ':'+'|escape:'html':'UTF-8'}" onclick="return !window.open(this.href, '_blank');" class="btn btn-primary-reverse btn-outline-primary light-button _blank">
                    <span>
                        <i class="icon-shopping-cart"></i>{if isset($module->price)}{if $module->price|floatval == 0}{l s='Free'}{elseif isset($module->id_currency)} &nbsp;&nbsp;{displayPrice price=$module->price currency=$module->id_currency}{/if}{/if}
                    </span>
                </a>
            </div>
        </div>
    {elseif !isset($module->not_on_disk)}
        <div class="col-12 col-sm-4 col-md-3 col-lg-2 mb-3">
            <div class="text-center">
                {if $module->optionsHtml|count > 0}
                <div class="btn-group">
                    {assign var=option value=$module->optionsHtml[0]}
                    {$option}
                    {if $module->optionsHtml|count > 1}
                    <button type="button" class="btn btn-primary-reverse btn-outline-primary light-button dropdown-toggle" data-toggle="dropdown" >
                        <span class="caret">&nbsp;</span>
                    </button>
                    <ul class="dropdown-menu pull-right">

                    {foreach $module->optionsHtml key=key item=option}
                        {if $key != 0}
                            {if strpos($option, 'title="divider"') !== false}
                                <li class="divider">BB</li>
                            {else}
                                <li>AAA{$option}</li>
                            {/if}
                        {/if}
                    {/foreach}
                    </ul>
                    {/if}
                </div>
                {/if}
            </div>
        </div>
    {else}
        <div class="col-12 col-sm-4 col-md-3 col-lg-2 mb-3">
            <div class="text-center">
                <form method="POST" action="{$module->options.install_url|escape:'html':'UTF-8'}">
                    <a href="{$module->options.install_url|escape:'html':'UTF-8'}" class="btn btn-primary-reverse btn-outline-primary light-button ">
                        <i class="icon-plus-sign-alt"></i>
                        {l s='Install'}
                    </a>
                </form>
            </div>
        </div>
    {/if}
</div>
