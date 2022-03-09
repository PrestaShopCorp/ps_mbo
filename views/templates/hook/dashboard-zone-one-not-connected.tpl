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

  <p> {l s='Connect to your account right now to enjoy updates (security and features) on all of your modules.' mod='psaddonsconnect'} </p><br>
  <p> {l s='Once you are connected, you will also enjoy weekly tips directly from your back office.' mod='psaddonsconnect'} </p> <br>

  <div class="text-center">
    <a class="btn btn-primary" id="page-header-desc-configuration-addons_connect" href="{$connect_button.href}" title="{$connect_button.help}">
      <i class="material-icons">{$connect_button.icon}</i> {$connect_button.desc}
    </a>
  </div>
</section>
