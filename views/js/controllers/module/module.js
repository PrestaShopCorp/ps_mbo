/**
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
 */

$(document).ready(function() {
	
	window.vApp = new Vue({
        el: '#psmbo',
        delimiters: ['[[', ']]'],
        data: {
			modules: [],
			categories: [],
			admin_module_ajax_url_psmbo: ''
        },
		methods: {
			getHref: function(module_name) {
				return window.vApp.admin_module_ajax_url_psmbo + '&action=GetModuleQuickView&module_name=' + module_name + '&ajax=1';
			},
			getAvgRateClass: function(avg_rate) {
				return 'module-stars module-star-ranking-grid-' + Math.round(avg_rate) + ' small';
			}
		}
    });
	
	$.ajax({
		type: 'POST',
		url: admin_module_ajax_url_psmbo,
		data: {
			ajax : true,
			action : 'GetModulesList',
		},
		success : function(data) {
			var parsedData = JSON.parse(data);
			window.vApp.modules = parsedData.modules;
			window.vApp.categories = parsedData.categories;
			
			console.log(window.vApp.categories);
			
			if (typeof filterCategoryTab !== 'undefined') {
				$.each(window.vApp.modules, function (key, value) {
					if (value.attributes.tab == filterCategoryTab) {
						value.attributes.visible = true;
					} else {
						value.attributes.visible = false;
					}
				}); 
			}
			$('[data-toggle="popover"]').popover();
		},
	});
	
	window.vApp.admin_module_ajax_url_psmbo = admin_module_ajax_url_psmbo;
	
	$('.fancybox-quick-view').fancybox({
		type: 'ajax',
		autoDimensions: false,
		autoSize: false,
		width: 600,
		height: 'auto',
		helpers: {
			overlay: {
				locked: false
			}
		}
	});
	
	$(document).on('click', '.module-read-more-grid-btn', function() {
		var name = $(this).attr('data-module-name');
		return true;
	});
	
});