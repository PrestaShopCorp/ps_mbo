{foreach $configure_toolbar_extra_buttons as $configure_toolbar_extra_button}
    <li>
        <a
                {if $configure_toolbar_extra_button.id} id="{$configure_toolbar_extra_button.id}"{/if}
                class="toolbar_btn {if $configure_toolbar_extra_button.class}{$configure_toolbar_extra_button.class}{/if}"
                href="{$configure_toolbar_extra_button.url}"
                title="{$configure_toolbar_extra_button.title}"
                {if $configure_toolbar_extra_button.data_attributes}
                    {foreach from=$configure_toolbar_extra_button.data_attributes item=attribute_value key=attribute_key}
                        data-{$attribute_key}="{$attribute_value}"
                    {/foreach}
                {/if}
        >
            {if $configure_toolbar_extra_button.icon}<i class="{$configure_toolbar_extra_button.icon}"></i>{/if}
            <div>{$configure_toolbar_extra_button.title}</div>
        </a>
    </li>
{/foreach}