/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
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
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
(function() {
    let ToolbarButtons = function(updateButtonSelector) {
        /** Initialize events. */
        this.initEvents = function() {
            updateButtonSelector.on('click', (e) => {
                const moduleName = updateButtonSelector.data('module-name');
                const upgradeUrl = updateButtonSelector.data('target');
                if (moduleName) {
                    this.checkForUpdates(upgradeUrl, moduleName);
                }
            });

            console.log('Events initialized for ModuleConfigureExtraButtons');
        }

        this.checkForUpdates = (upgradeUrl, moduleName) => {
            let upgradeModuleRequest = $.ajax({
                type: 'POST',
                dataType: 'json',
                url: upgradeUrl,
                data: {
                    moduleName: moduleName,
                }
            });

            upgradeModuleRequest.done(function(data) {
                window.$.growl({
                    title: '',
                    size: 'large',
                    message: data.msg,
                });
                console.log(`Module ${moduleName} successfully upgraded`);
                console.log(data);
            });

            upgradeModuleRequest.fail(function(jqXHR, textStatus, errorThrown) {
                console.error(`Module ${moduleName} upgrade failed`)
                if (undefined !== jqXHR.responseText) {
                    $.growl.error({message: jqXHR.responseText});
                }
                if (undefined !== jqXHR.responseJSON && undefined !== jqXHR.responseJSON.content) {
                    $.growl.error({message: jqXHR.responseJSON.content});
                }
            });
        }
    }

    $(() => {
        // instantiate the toolbar button controller
        let toolbarButtons = new ToolbarButtons(
            $('#mbo-desc-module-update')
        );

        toolbarButtons.initEvents();
    })
})();