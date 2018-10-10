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
<div id="module-modal-import" class="modal modal-vcenter fade" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title module-modal-title">{l s='Upload a module' d='Admin.Modules.Feature'}</h4>
                <button id="module-modal-import-closing-cross" type="button" class="close">&times;</button>
            </div>
            <div class="modal-body">
{*                {% if level <= constant('PrestaShopBundle\\Security\\Voter\\PageVoter::LEVEL_UPDATE') %}
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-danger" role="alert">
                                <p class="alert-text">
                                    {{ errorMessage }}
                                </p>
                            </div>
                        </div>
                    </div>
                {% else %}*}
                    <div class="row">
                        <div class="col-md-12">
                            <form action="#" class="dropzone" id="importDropzone">
                                <div class="module-import-start">
                                    <i class="module-import-start-icon material-icons">cloud_upload</i><br/>
                                    <p class=module-import-start-main-text>
										{l s='Drop your module archive here or' d='Admin.Modules.Feature'}
										<a href="#" class="module-import-start-select-manual">{l s='select file' d='Admin.Modules.Feature'}</a>
                                    </p>
                                    <p class=module-import-start-footer-text>
										{l s='Please upload one file at a time, .zip or tarball format (.tar, .tar.gz or .tgz).' d='Admin.Modules.Help'}
										{l s='Your module will be installed right after that.' d='Admin.Modules.Help'}
                                    </p>
                                </div>
                                <div class='module-import-processing'>
                                    <!-- Loader -->
                                    <div class="spinner"></div>
                                    <p class=module-import-processing-main-text>
										{l s='Installing module...' d='Admin.Modules.Notification'}
									</p>
                                    <p class=module-import-processing-footer-text>
										{l s='It will close as soon as the module is installed. It won\'t be long!' d='Admin.Modules.Notification'}
                                    </p>
                                </div>
                                <div class='module-import-success'>
                                    <i class="module-import-success-icon material-icons">done</i><br/>
                                    <p class='module-import-success-msg'>{l s='Module installed!' d='Admin.Modules.Notification'}</p>
                                    <p class="module-import-success-details"></p>
                                    <a class="module-import-success-configure btn btn-primary-reverse btn-outline-primary light-button" href='#'>{l s='Configure' d='Admin.Actions'}</a>
                                </div>
                                <div class='module-import-failure'>
                                    <i class="module-import-failure-icon material-icons">error</i><br/>
                                    <p class='module-import-failure-msg'>{l s='Oops... Upload failed.' d='Admin.Modules.Notification'}</p>
                                    <a href="#" class="module-import-failure-details-action">{l s='What happened?' d='Admin.Modules.Help'}</a>
                                    <div class='module-import-failure-details'></div>
                                    <a class="module-import-failure-retry btn btn-tertiary" href='#'>{l s='Try again' d='Admin.Actions'}</a>
                                </div>
                                <div class='module-import-confirm'>
                                </div>
								<input type="file" multiple="multiple" class="dz-hidden-input" accept=".zip, .tar" style="visibility: hidden; position: absolute; top: 0px; left: 0px; height: 0px; width: 0px;">
                            </form>
                        </div>
                    </div>
{*                {% endif %}*}
            </div>
            <div class="modal-footer">
            </div>
        </div>
    </div>
</div>
