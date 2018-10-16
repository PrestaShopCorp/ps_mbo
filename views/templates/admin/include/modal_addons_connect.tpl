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
 
{if $addons_connect['connected'] === false}
	<div id="module-modal-addons-connect" class="modal  modal-vcenter fade" role="dialog">
	  <div class="modal-dialog">
		<!-- Modal content-->
		<div class="modal-content">
			<div class="modal-header">
			  <h4 class="modal-title module-modal-title">{l s='Connect to Addons marketplace' d='Admin.Modules.Feature'}</h4>
			  <button type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">
		   {* {% if level <= constant('PrestaShopBundle\\Security\\Voter\\PageVoter::LEVEL_UPDATE') %}
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
						<p>
							{l s='Link your shop to your Addons account to automatically receive important updates for the modules you purchased. Don\'t have an account yet?' d='Admin.Modules.Feature'}
							<a href="https://addons.prestashop.com/authentication.php" target="_blank">{l s='Sign up now' d='Admin.Modules.Feature'}</a>
						</p>
						{* /prestashop17/admin-dev/index.php/addons/login?_token=4xDZLkW-GyILZheADOFUaFc64RW5FrgiYbJLIpPlWXQ *}
						<form id="addons-connect-form"  action="{$addons_connect['login_url']}" method="POST">
							<div class="form-group">
							  <label for="module-addons-connect-email">{l s='Email address' d='Admin.Global'}</label>
							  <input name="username_addons" type="email" class="form-control" id="module-addons-connect-email" placeholder="Email">
							</div>
							<div class="form-group">
							  <label for="module-addons-connect-password">{l s='Password' d='Admin.Global'}</label>
							  <input name="password_addons" type="password" class="form-control" id="module-addons-connect-password" placeholder="Password">
							</div>
							<div class="checkbox">
							  <label>
								<input name="addons_remember_me" type="checkbox"> {l s='Remember me' d='Admin.Global'}
							  </label>
							</div>
							<button type="submit" class="btn btn-primary">{l s='Let\'s go!' d='Admin.Actions'}</button>
							<div id="addons_login_btn" class="spinner" style="display:none;"></div>
						</form>
						<p>
							<a href="https://addons.prestashop.com/password.php" target="_blank">{l s='Forgot your password?' d='Admin.Global'}</a>
						</p>
					</div>
			  </div>
	{*        {% endif %}*}
		  </div>
		</div>
	  </div>
	</div>
{else}
	<div id="module-modal-addons-logout" class="modal  modal-vcenter fade" role="dialog">
		<div class="modal-dialog">
			<!-- Modal content-->
			<div class="modal-content">
				<div class="modal-header">
				  <button type="button" class="close" data-dismiss="modal">&times;</button>
				  <h4 class="modal-title module-modal-title">{l s='Confirm logout' d='Admin.Modules.Feature'}</h4>
				</div>
				<div class="modal-body">
					<div class="row">
						<div class="col-md-12">
							<p>
								{l s='You are about to log out your Addons account. You might miss important updates of Addons you\'ve bought.' d='Admin.Modules.Notification'}
							</p>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<input type="button" class="btn btn-default uppercase" data-dismiss="modal" value="{l s='Cancel' d='Admin.Actions'}">
					{* path('admin_addons_logout') *}
					<a class="btn btn-primary uppercase" href="{$addons_connect['logout_url']}" id="module-modal-addons-logout-ack">{l s='Yes, log out' d='Admin.Modules.Feature'}</a>
				</div>
			</div>
		</div>
	</div>
{/if}