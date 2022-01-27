'use strict';
/**
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
 */

const {$} = window;

var mbo = {};

(function() {
    var pageMap = {
        addonsLoginButtonSelector: '#addons_login_btn',
        addonsConnectModalBtnSelector: '#page-header-desc-configuration-addons_connect',
        addonsLogoutModalBtnSelector: '#page-header-desc-configuration-addons_logout',
        addonsImportModalBtnSelector: '#page-header-desc-configuration-add_module',
        addonsConnectModalSelector: '#module-modal-addons-connect',
        addonsLogoutModalSelector: '#module-modal-addons-logout',
        addonsConnectForm: '#addons-connect-form',
    };

    var AddonsConnector = function(pageMap) {
        this.initConnect = function() {
            // Make addons connect modal ready to be clicked
            if ($(pageMap.addonsConnectModalBtnSelector).attr('href') === '#') {
                $(pageMap.addonsConnectModalBtnSelector).attr('data-toggle', 'modal');
                $(pageMap.addonsConnectModalBtnSelector).attr('data-target', pageMap.addonsConnectModalSelector);
            }

            if ($(pageMap.addonsLogoutModalBtnSelector).attr('href') === '#') {
                $(pageMap.addonsLogoutModalBtnSelector).attr('data-toggle', 'modal');
                $(pageMap.addonsLogoutModalBtnSelector).attr('data-target', pageMap.addonsLogoutModalSelector);
            }

            $('body').on('submit', pageMap.addonsConnectForm, function initializeBodySubmit(event) {
                event.preventDefault();
                event.stopPropagation();

                $.ajax({
                    method: 'POST',
                    url: $(this).attr('action'),
                    dataType: 'json',
                    data: $(this).serialize(),
                    beforeSend: () => {
                        $(pageMap.addonsLoginButtonSelector).show();
                        $('button.btn[type="submit"]', pageMap.addonsConnectForm).hide();
                    },
                }).done((response) => {
                    if (response.success === 1) {
                        window.location.reload();
                    } else {
                        $.growl.error({message: response.message});
                        $(pageMap.addonsLoginButtonSelector).hide();
                        $('button.btn[type="submit"]', pageMap.addonsConnectForm).fadeIn();
                    }
                });
            });
        }
    }

    $(document).ready(function() {
        var addonsConnector = new AddonsConnector(pageMap);
        addonsConnector.initConnect();
    })
})();