{if isset($from) && $from == 'footer'}
    <div class="panel" {if isset($panel_id)}id="{$panel_id}"{/if}>
        <h3>
            <i class="icon-list-ul"></i>
            {if isset($panel_title)}{$panel_title|escape:'html':'UTF-8'}{else}{l s='Modules list'}{/if}
        </h3>
	{/if}
    <div class="modules_list_container_tab row">
        <div class="col-lg-12">
            {if count($modules_list)}
                <table class="table">
                    {counter start=1  assign="count"}
                        {foreach from=$modules_list item=module}
                            {include file='./tab_module_line-legacy.tpl' class_row={cycle values=",row alt"}}
                        {counter}
                    {/foreach}
                </table>
                {if $controller_name == 'AdminPayment' && isset($view_all)}
                    <div class="panel-footer">
                        <div class="col-lg-4 col-lg-offset-4">
                            <a class="btn btn-default btn-block" href="{$link->getAdminLink('AdminPsMboModule', true, [], ['filterCategoryTab' => 'payments_gateways'])|escape:'html':'UTF-8'}">
                                <i class="process-icon-payment"></i>
                                {l s='View all available payments solutions'}
                            </a>
                        </div>
                    </div>
                {/if}
            {else}
                <table class="table">
                    <tr>
                        <td>
                            <div class="alert alert-warning">
                            {if $controller_name == 'AdminPayment'}
                            {l s='It seems there are no recommended payment solutions for your country.'}<br />
                            <a class="_blank" href="https://www.prestashop.com/en/contact-us">{l s='Do you think there should be one? Let us know!'}</a>
                            {else}{l s='No modules available in this section.'}{/if}</div>
                        </td>
                    </tr>
                </table>
            {/if}
        </div>
    </div>
{if isset($from) && $from == 'footer'}
    </div>
{/if}

{if isset($from) && $from == 'tab'}
    <div class="alert alert-addons row-margin-top" role="alert">
        <p class="alert-text">
          <a href="http://addons.prestashop.com/?utm_source=back-office&amp;utm_medium=dispatch&amp;utm_campaign=back-office-en-US&amp;utm_content=download" onclick="return !window.open(this.href);">{l s='More modules on addons.prestashop.com'}</a>
        </p>
    </div>
{/if}
