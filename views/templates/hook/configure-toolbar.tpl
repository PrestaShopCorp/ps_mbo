{foreach $configure_toolbar_extra_buttons as $configure_toolbar_extra_button}
  <li>
    <a
      class="toolbar_btn btn-primary"
      href="{$configure_toolbar_extra_button.url}"
      title="{$configure_toolbar_extra_button.title}"
    >
      <div>{$configure_toolbar_extra_button.title}</div>
    </a>
  </li>
{/foreach}
