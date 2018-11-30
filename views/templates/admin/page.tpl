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
<script>
  var admin_module_ajax_url_psmbo = '{$admin_module_ajax_url_psmbo}';
  $('#product_form_open_help').attr('href', $('#toolbar-nav li a.btn-help').attr('href'));
  $('#toolbar-nav li a.btn-help').hide();
</script>

{if isset($filterCategoryTab)}
    <script>
      var filterCategoryTab = '{$filterCategoryTab}';
    </script>
{/if}

{if isset($javascript_urls)}
    <script>
      var mboJavascriptUrls = {$javascript_urls};
    </script>
{/if}

<div id="psmbo" class="row justify-content-center">

    <div class="col-lg-10 module-catalog-page">
        {include file="./include/menu_top.tpl"}
        <div id="modules-list-container-all" class="row modules-list">

            <div class="col-sm-12 text-center">
                <button class="btn-primary-reverse onclick unbind spinner hide"></button>
            </div>

            <div
              v-for="module in modules"
              v-show="module.attributes.visible"
              class="module-item module-item-grid col-md-12 col-lg-6 col-xl-3"
              v-bind:data-id="module.attributes.id"
              v-bind:data-name="module.attributes.displayName"
              v-bind:data-scoring="module.attributes.avgRate"
              v-bind:data-logo="module.attributes.img"
              v-bind:data-author="module.attributes.author"
              v-bind:data-version="module.attributes.version"
              v-bind:data-description="module.attributes.description"
              v-bind:data-tech-name="module.attributes.name"
              v-bind:data-child-categories="module.attributes.categoryName"
              v-bind:data-categories="module.attributes.categoryParentId"
              v-bind:data-type="module.attributes.product_type"
            >
                <div class="module-item-wrapper-grid">
                    <div class="module-item-heading-grid">
                        <div class="module-logo-thumb-grid">
                            <img v-bind:src="module.attributes.img" v-bind:alt="module.attributes.displayName"/>
                        </div>
                        <h3
                          class="text-ellipsis module-name-grid"
                          data-toggle="pstooltip"
                          data-placement="top"
                          v-bind:title="module.attributes.displayName"
                        >
                            <span v-if="module.attributes.displayName">
                                [[ module.attributes.displayName ]]
                            </span>
                            <span v-else> [[ module.attributes.name ]]</span>

                            <span v-if="module.attributes.prestatrust">
                                <img v-bind:src="module.attributes.prestatrust.pico">
                            </span>

                        </h3>
                        <div v-if="module.attributes.product_type === 'service'" class="text-ellipsis small-text module-version-author-grid">
                            {l s='Service by'} <b>[[ module.attributes.author ]]</b>
                        </div>
                        <div v-else class="text-ellipsis small-text module-version-author-grid">
                            v[[ module.attributes.version ]] - {l s='by'} <b>[[ module.attributes.author ]]</b>
                        </div>
                    </div>
                    <div class="module-quick-description-grid small no-padding mb-0">
                        <div class="module-quick-description-text">
                            [[ module.attributes.description ]]
                            <span v-if="module.attributes.description.length > 0 && module.attributes.description.length < module.attributes.fullDescription.length">
                                ...
                            </span>
                        </div>
                        <div class="module-read-more-grid">
                            <a v-if="module.attributes.id != '0'" v-bind:href="getHref(module.attributes.name)" class="fancybox-quick-view url" v-bind:data-module-name="module.attributes.name">
                                {l s='Read More' d='Admin.Modules.Feature'}
                            </a>
                        </div>
                    </div>

                    <div class="module-container module-quick-action-grid clearfix">
                        <div class="badges-container">
                            <div v-for="badge in module.attributes.badges">
                                <img v-bind:src="badge.img" v-bind:alt="badge.label"/>
                                <span>[[ badge.label ]]</span>
                            </div>
                        </div>
                        <hr v-if="module.attributes.badges" />
                        <div v-if="module.attributes.nbRates > 0" class="float-left" v-bind:class="getAvgRateClass(module.attributes.avgRate)">
                            ([[ module.attributes.nbRates ]])
                        </div>

                        <div class="float-right module-price">
                            <span v-if="module.attributes.price === 0" class="pt-2" >
                                {l s='Free'}
                            </span>
                            <span v-else>
                                [[ module.attributes.price ]] {$currency_symbol}
                            </span>
                        </div>

                        {if isset($requireBulkActions) && $requireBulkActions == true}
                            <div class="float-right module-checkbox-bulk-grid">
                                <input type="checkbox" v-bind:data-name="module.attributes.displayName" v-bind:data-tech-name="module.attributes.name" />
                            </div>
                        {/if}

                        {include file="./include/action_menu.tpl"}
                    </div>
                </div>
            </div>


            <div id="see-results-addons" class="module-item module-item-grid col-md-12 col-lg-6 col-xl-3 hidden">
                <div class="module-item-wrapper-grid">
                    <div class="module-item-heading-grid" style="height: 350px;">
                        <div style="line-height: 220px;">
                            <img src="{$bo_img}preston.png?1.7.0" alt="{l s='Exit to PrestaShop Addons Marketplace'}">
                        </div>
                        {l s='See all results for your search on'}<br>
                        <a class="url" href="#" target="_blank">{l s='PrestaShop Addons Marketplace'}</a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <input type="hidden" id="install_url" value="{(isset($install_url)) ? $install_url : ''}" >
    <input type="hidden" id="module_controller_token" value="{(isset($module_controller_token)) ? $module_controller_token : ''}" >
    <input type="hidden" id="notification_count_url" value="{(isset($notification_count_url)) ? $notification_count_url : ''}" >

    {include file="./include/modal_import.tpl"}
    {include file="./include/modal_confirm_prestatrust.tpl"}

</div>


<div id="fancyBox">

</div>
