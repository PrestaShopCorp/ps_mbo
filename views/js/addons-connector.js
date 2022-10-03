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

(function() {
    var pageMap = {
        addonsLoginButtonSelector: '#addons_login_btn',
        addonsConnectModalBtnSelector: '#page-header-desc-configuration-addons_connect',
        addonsConnectModalBtnSelectorMobile: '#page-header-desc-floating-configuration-addons_connect',
        addonsLogoutModalBtnSelector: '#page-header-desc-configuration-addons_logout',
        addonsLogoutModalBtnSelectorMobile: '#page-header-desc-floating-configuration-addons_logout',
        addonsImportModalBtnSelector: '#page-header-desc-configuration-add_module',
        addonsConnectModalSelector: '#module-modal-addons-connect',
        addonsLogoutModalSelector: '#module-modal-addons-logout',
        addonsConnectForm: '#addons-connect-form',
    };

    var AddonsConnector = function(pageMap) {
        this.initConnect = function() {
            // Make addons connect modal ready to be clicked
            this.switchToModal(pageMap.addonsConnectModalBtnSelector, pageMap.addonsConnectModalSelector);
            this.switchToModal(pageMap.addonsConnectModalBtnSelectorMobile, pageMap.addonsConnectModalSelector);
            this.switchToModal(pageMap.addonsLogoutModalBtnSelector, pageMap.addonsLogoutModalSelector);
            this.switchToModal(pageMap.addonsLogoutModalBtnSelectorMobile, pageMap.addonsLogoutModalSelector);

            window.$('body').on('submit', pageMap.addonsConnectForm, function initializeBodySubmit(event) {
                event.preventDefault();
                event.stopPropagation();

                window.$.ajax({
                    method: 'POST',
                    url: window.$(this).attr('action'),
                    dataType: 'json',
                    data: window.$(this).serialize(),
                    beforeSend: () => {
                        window.$(pageMap.addonsLoginButtonSelector).show();
                        window.$('button.btn[type="submit"]', pageMap.addonsConnectForm).hide();
                    },
                }).done((response) => {
                    if (response.success === 1) {
                        window.location.reload();
                    } else {
                        window.$.growl.error({message: response.message});
                        window.$(pageMap.addonsLoginButtonSelector).hide();
                        window.$('button.btn[type="submit"]', pageMap.addonsConnectForm).fadeIn();
                    }
                });
            });
        }

        /**
         * @private
         */
        this.switchToModal = function(element, target) {
            if (window.$(element).attr('href') === '#') {
                // modify jQuery cache
                window.$(element).data('toggle', 'modal');
                window.$(element).data('target', target);
                // modify the DOM
                window.$(element).attr('data-toggle', 'modal');
                window.$(element).attr('data-target', target);
            }
        }
    }

    window.$(document).ready(function() {
        var addonsConnector = new AddonsConnector(pageMap);
        addonsConnector.initConnect();
    })
})();
