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
				return window.vApp.admin_module_ajax_url_psmbo + '&action=GetMboModuleQuickView&module_name=' + module_name + '&ajax=1';
			},
			getAvgRateClass: function(avg_rate) {
				return 'module-stars module-star-ranking-grid-' + Math.round(avg_rate) + ' small';
			},
			visibleModules: function() {
				var visibleModules = 0;
				
				if (typeof window.vApp !== 'undefined' && typeof window.vApp.modules !== 'undefined') {
					$.each(window.vApp.modules, function(index, value) {
						if (value.attributes.visible === true) {
							visibleModules++;
						}
					});
				}
				
				return visibleModules;
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
		beforeSend: function() {
			$('#psmbo .btn-primary-reverse.spinner').css({
				'background-color': 'inherit'
			});
			$('#psmbo .btn-primary-reverse.spinner').removeClass('hide');
			
		},
		success : function(data) {
			var parsedData = JSON.parse(data);
			window.vApp.modules = parsedData.modules;
			window.vApp.categories = parsedData.categories;
			
			if (typeof filterCategoryTab !== 'undefined') {
				// should use a promise, to improve
				setTimeout(function () {
					$('.module-category-menu[data-category-display-ref-menu=' + filterCategoryTab + ']').trigger('click');
				}, 300);
			}
			$('[data-toggle="popover"]').popover();
			$('#psmbo .btn-primary-reverse.spinner').addClass('hide');
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
	
	var urlToCall = $('#notification_count_url').val();
	if (urlToCall !== '') {
		var tabToUpdate = $("#subtab-AdminModulesUpdates");
		if (tabToUpdate.length === 0) {
			return;
		}
        
		var tabToConfigure = $("#subtab-AdminModulesNotifications");
		if (tabToConfigure.length === 0) {
			return;
		}
		
		$.getJSON(urlToCall, function(badge) {
	        tabToUpdate.append('<span class="notification-container">\
	            <span class="notification-counter">'+badge.to_update+'</span>\
	          </span>\
	        ');
            
	        tabToConfigure.append('<span class="notification-container">\
	            <span class="notification-counter">'+badge.to_configure+'</span>\
	          </span>\
	        ');
		}).fail(function() {
			console.error('Could not retrieve module notifications count.');
		});
	}
	
});