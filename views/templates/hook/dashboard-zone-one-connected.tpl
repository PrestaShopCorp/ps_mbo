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

<section id="mbo-advices-and-updates" class="panel widget">
  <div class="panel-heading">
    <i class="icon-puzzle-piece"></i> {l s='TIPS & UPDATES' mod='psaddonsconnect'}
  </div>

  {if $advice }
    <header>
      <h4> {l s='Tip of the moment' mod='psaddonsconnect'} </h4><p><br>
    </header>
    <img src="{$img_path|escape:'htmlall':'UTF-8'}lamp-selection-moment.jpg" alt="lamp" class="pull-left">

    <div class="row">
      <div class="col-md-10">
        <p>
          {$advice|escape:'quotes':'UTF-8'}
        </p>
      </div>
    </div>

    <a href="{$link_advice|escape:'htmlall':'UTF-8'}" target="_blank" class="pull-right"> {l s='See the entire selection' mod='psaddonsconnect'} > </a> <p><br>
  {/if}
  <h4> {l s='Practical links' mod='psaddonsconnect'} </h4>

  {l s='Modules to' mod='psaddonsconnect'} <a href="{$practical_links['traffic']|escape:'htmlall':'UTF-8'}" target="_blank"> {l s='increase your traffic' mod='psaddonsconnect'} ></a><br>
  {l s='Modules to' mod='psaddonsconnect'} <a href="{$practical_links['conversion']|escape:'htmlall':'UTF-8'}" target="_blank"> {l s='boost your conversions' mod='psaddonsconnect'} ></a><br>
  {l s='Modules to' mod='psaddonsconnect'} <a href="{$practical_links['averageCart']|escape:'htmlall':'UTF-8'}" target="_blank"> {l s='increase your clients\' average cart' mod='psaddonsconnect'} ></a><br>
  {l s='Selection of modules recommended for' mod='psaddonsconnect'} <a href="{$practical_links['businessSector']|escape:'htmlall':'UTF-8'}" target="_blank"> {l s='your business sector' mod='psaddonsconnect'} ></a><br>
</section>
