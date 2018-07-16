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
 
{if !$isSymfonyContext}
    <li style="display:none;">
        <a id="page-header-desc-carrier-new_carrier" class="toolbar_btn  pointer" href="" title="{l s='Recommended Modules and Services'}">
            <i class="process-icon-modules-list"></i>
            <div>{l s='Recommended Modules and Services'}</div>
        </a>
    </li>
{/if}

<script>
    
    var isSymfonyContext = {if $isSymfonyContext}true{else}false{/if};
    var admin_module_ajax_url_psmbo = '{$admin_module_ajax_url_psmbo}';
    var controller = '{$controller}';
    
    if (isSymfonyContext === false) {
        
        $(document).ready(function() {
            
            $('.process-icon-modules-list').parent('a').prop('href', admin_module_ajax_url_psmbo);
            
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
        });
    }
	
	$(document).on('click', '#page-header-desc-configuration-modules-list', function(event) {
		event.preventDefault();
		openModalOrRedirect(isSymfonyContext);
	});
	
	$('.process-icon-modules-list').parent('a').unbind().bind('click', function (event) {
		event.preventDefault();
		openModalOrRedirect(isSymfonyContext);
	});
    
    function openModalOrRedirect(isSymfonyContext) {
        if (isSymfonyContext === false) {
            $('#modules_list_container').modal('show');
            openModulesList();
        } else {
            window.location.href = admin_module_ajax_url_psmbo;
        }
    }
	
    function openModulesList() {
        $.ajax({
            type: 'POST',
            url: admin_module_ajax_url_psmbo,
            data: {
                ajax : true,
                action : 'GetTabModulesList',
                controllerName: controller
            },
            success : function(data) {
                $('#modules_list_container_tab_modal').html(data).slideDown();
                $('#modules_list_loader').hide();
            },
        });
    }
	
	
</script>
