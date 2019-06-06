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

<div class="toolbar-icons">
	<div class="wrapper">
		<a class="btn btn-primary  pointer" id="page-header-desc-configuration-add_module" href="#" title="" data-toggle="modal" data-placement="bottom" data-original-title="Installer un module" data-target="#module-modal-import">
			<i class="material-icons">cloud_upload</i>
			{l s='Install a module'}
		</a>

		{if isset($addons_connect)}
			{if $addons_connect['connected'] === true}
			<a class="btn btn-primary pointer" id="page-header-desc-configuration-addons_logout" href="#" title="" data-toggle="modal" data-placement="bottom" data-original-title="{l s='Synchronized with Addons marketplace' d='Admin.Modules.Notification'}" data-target="#module-modal-addons-logout">
				<i class="material-icons">exit_to_app</i>
				{$addons_connect['email']}
			</a>
			{else}
				<a class="btn btn-primary pointer" id="page-header-desc-configuration-addons_connect" href="#" title="" data-toggle="modal" data-placement="bottom" data-original-title="{l s='Connect to Addons marketplace' d='Admin.Modules.Feature'}" data-target="#module-modal-addons-connect">
					<i class="material-icons">vpn_key</i>
					{l s='Connect to Addons marketplace' d='Admin.Modules.Feature'}
				</a>
			{/if}
		{/if}

		<a class="btn btn-outline-secondary btn-help btn-sidebar" href="" title="{l s='Help'}" data-toggle="sidebar" data-target="#right-sidebar" data-url="" id="product_form_open_help">
			{l s='Help'}
		</a>
	</div>
</div>