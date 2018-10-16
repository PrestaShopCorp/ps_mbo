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