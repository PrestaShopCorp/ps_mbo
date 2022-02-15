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

<script>
  var dashboardNewsAjaxUrl = '{$link->getAdminLink('DashboardNews', true, ["route" => "admin_mbo_dashboard_blog_rss"])}';
  var translationsDashboardMbo = {
    new_read_more: '{l s='Read more' js=1}',
  }

  if('function' === typeof getDashboardMBONewsBlogRss) {
    getDashboardMBONewsBlogRss(dashboardNewsAjaxUrl, translationsDashboardMbo);
  }
</script>
<section class="dash_news panel">
  <h3><i class="icon-rss"></i> {l s='PrestaShop News' d='Modules.Mbo.Dashboardzonethree'}</h3>
  <div class="dash_news_content"></div>
  <div class="text-center">
    <h4>
      <a href="http://www.prestashop.com/blog/" onclick="return !window.open(this.href);">
          {l s='Find more news' d='Modules.Mbo.Dashboardzonethree'}
      </a>
    </h4>
  </div>
</section>
<section id="dash_version" class="visible-lg">
  <iframe style="overflow:hidden;border:none" src="{$new_version_url|escape:'html':'UTF-8'}" ></iframe>
</section>
<section class="dash_links panel">
  <h3><i class="icon-link"></i> {l s="We stay by your side!" d='Modules.Mbo.Dashboardzonethree'}</h3>
  <dl>
    <dt>
      <a href="{$help_center_link}" target="_blank" rel="noopener noreferrer nofollow">
          {l s="Help Center" d='Modules.Mbo.Dashboardzonethree'}
      </a>
    </dt>
    <dd>{l s="Documentation, support, experts, training... PrestaShop and all of its community are here to guide you" d='Modules.Mbo.Dashboardzonethree'}</dd>
  </dl>
  <dl>
    <dt>
      <a href="https://addons.prestashop.com?utm_source=back-office&amp;utm_medium=links&amp;utm_campaign=addons-{$lang_iso}&amp;utm_content=download17" target="_blank" rel="noopener noreferrer nofollow">
            {l s="PrestaShop Marketplace" d='Modules.Mbo.Dashboardzonethree'}
      </a>
    </dt>
    <dd>{l s="Traffic, conversion rate, customer loyalty... Increase your sales with all of the PrestaShop modules and themes" d='Modules.Mbo.Dashboardzonethree'}</dd>
  </dl>
</section>
