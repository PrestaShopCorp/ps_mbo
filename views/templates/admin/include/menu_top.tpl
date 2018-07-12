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
<div class="module-top-menu">
    <div class="row">
        <div class="col-md-8">
			<div class="input-group" id="search-input-group">
                <input type="text" id="module-search-bar" class="form-control" style="display: none;">
{*				<div class="pstaggerWrapper "><div class="pstaggerTagsWrapper "></div><div class="pstaggerAddTagWrapper "><input class="pstaggerAddTagInput "></div></div>*}
                <div class="input-group-btn">
                    <button class="btn btn-primary float-right search-button" id="module-search-button">
                        <i class="material-icons">search</i>
                        {l s='Search' d='Admin.Actions'}
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-4 module-menu-item">
			{include file="./dropdown_categories.tpl"}
        </div>
    </div>
</div>

<hr class="top-menu-separator"/>

<div class="module-sorting-menu">
    <div class="row">
        <div class="col-lg-6">
            <div class="module-sorting-search-wording">
                <span id="selected_modules" class="module-search-result-wording"><span v-html="visibleModules()"></span> {l s='%nbModules% modules and services selected for you' d='Admin.Modules.Feature' sprintf=['%nbModules%' => '']}</span>
                <span class="help-box" data-toggle="popover"
                    data-title="{l s='Selection' d='Admin.Modules.Feature'}"
                    data-content="{l s='Customize your store with this selection of modules recommended for your shop, based on your country, language and version of PrestaShop. It includes the most popular modules from our Addons marketplace, and free partner modules.' d='Admin.Modules.Help'}">
                </span>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="module-sorting module-sorting-author float-right">
                <select id="sort_module" class="custom-select sort-component">
                  <option value="" disabled selected>- {l s='Sort by' d='Admin.Actions'} -</option>
                  <option value="displayName">{l s='Name' d='Admin.Global'}</option>
                  <option value="price">{l s='Increasing Price' d='Admin.Modules.Feature'}</option>
                  <option value="price-desc">{l s='Decreasing Price' d='Admin.Modules.Feature'}</option>
                  <option value="scoring-desc">{l s='Popularity' d='Admin.Modules.Feature'}</option>
                </select>
            </div>
        </div>
    </div>
</div>
