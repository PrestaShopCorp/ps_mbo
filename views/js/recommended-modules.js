'use strict';
/*
 * 2007-2019 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
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
 * @copyright 2007-2019 PrestaShop SA
 * @license   https://opensource.org/licenses/afl-3.0 Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *
 */

var mbo = {};

(function() {
  var pageMapDefault = {
    toolbarButtonsContainer: '#toolbar-nav',
    toolbarHelpButton: '#toolbar-nav li:last-of-type > a.btn-help',
    toolbarLastElement: '#toolbar-nav li:last-of-type',
    recommendedModulesButton: '#recommended-modules-button',
    fancybox: '.fancybox-quick-view',
    modulesListModal: '#modules_list_container',
    modulesListModalContent: '#modules_list_container_tab_modal',
    modulesListLoader: '#modules_list_loader',
  };

  var pageMapNewTheme = {
    toolbarButtonsContainer: '.toolbar-icons .wrapper',
    toolbarHelpButton: '.toolbar-icons a.btn-help',
    toolbarLastElement: '.toolbar-icons a:last-of-type',
    recommendedModulesButton: '#recommended-modules-button',
  };

  /**
   * Detects which theme we are currently on
   * @constructor
   */
  var ThemeDetector = function() {
    var isNewTheme = $('link').filter(function() {
      return $(this).attr('href').match(RegExp('/themes/new-theme/'));
    }).length > 0;

    /**
     * @return {boolean}
     */
    this.isNewTheme = function() {
      return isNewTheme;
    }
  };

  /**
   * Handles page interactions
   *
   * @param {object} pageMap
   * @constructor
   */
  var Page = function(pageMap) {

    /**
     * Indicates if the help button is the last one in the toolbar
     * @return {boolean}
     */
    var lastElementIsHelpButton = function() {
      return $(pageMap.toolbarHelpButton).length > 0;
    };

    /**
     * Inserts the button before the help one
     * @param {RecommendedModulesButton} button
     */
    var insertItBeforeHelpButton = function(button) {
      $(pageMap.toolbarLastElement).before(button.getMarkup());
    };

    /**
     * Inserts the button as the last item in the toolbar
     * @param {RecommendedModulesButton} button
     */
    var insertItLastInToolbar = function(button) {
      $(pageMap.toolbarButtonsContainer).append(button.getMarkup());
    };

    /**
     * Inserts the provided button in the toolbar
     *
     * @param {RecommendedModulesButton} button
     * @return this
     */
    this.insertToolbarButton = function(button) {
      if (lastElementIsHelpButton()) {
        insertItBeforeHelpButton(button);
      } else {
        insertItLastInToolbar(button);
      }

      return this;
    };
  };

  /**
   * Handles markup for the Recommended modules button
   *
   * @param {object} trad - Translations dictionary
   * @param {boolean} isNewTheme
   * @param {string} href
   * @constructor
   */
  var RecommendedModulesButton = function(trad, isNewTheme, href) {
    var label = trad['Recommended Modules and Services'];
    var buttonId = 'recommended-modules-button';
    var $markup;

    if (isNewTheme) {
      $markup = $(
        '<a class="btn btn-outline-secondary" id="' + buttonId + '" href="' + href + '" title="' + label + '">\n' +
        label +
        '</a>'
      );
    } else {
      $markup = $(
        '<li id="recommended-modules-button-container">\n' +
        '  <a id="' + buttonId + '" class="toolbar_btn pointer" href="' + href + '" title="' + label + '">\n' +
        '    <i class="process-icon-modules-list"></i>\n' +
        '    <div>' + label + '</div>\n' +
        '  </a>\n' +
        '</li>'
      );
    }

    /**
     * Returns the button's markup
     * @return {jQuery|HTMLElement}
     */
    this.getMarkup = function() {
      return $markup;
    }
  };

  /**
   *
   * @param {object} pageMap
   * @param {string} recommendedModulesButtonUrl
   * @param {string} currentControllerName
   * @constructor
   */
  var RecommendedModulesPopinHandler = function(pageMap, recommendedModulesButtonUrl, currentControllerName) {

    var initPopin = function() {
      $(pageMap.fancybox).fancybox({
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
    };

    var openModulesList = function() {
      $(pageMap.modulesListModal).modal('show');

      $.ajax({
        type: 'POST',
        url: recommendedModulesButtonUrl,
        data: {
          ajax: true,
          action: 'GetTabModulesList',
          controllerName: currentControllerName
        },
        success: function(data) {
          $(pageMap.modulesListModalContent).html(data).slideDown();
          $(pageMap.modulesListLoader).hide();
        },
      });
    };

    var bindButtonEvents = function() {
      // wait for dom ready
      $(document).on('click', pageMap.recommendedModulesButton, function(event) {
        event.preventDefault();
        openModulesList();
      });
    };

    this.initialize = function() {
      // wait for dom ready
      $(function() {
        initPopin();
        bindButtonEvents();
      });
    };

  };

  /**
   * Inserts the recommended modules button in the toolbar
   *
   * @param {object} config
   * @param {object} config.lang - Object containing translations
   * @param {string} config.recommendedModulesButtonUrl - URL for button
   * @param {string} config.controller - Current controller name
   */
  mbo.insertToolbarButton = function(config) {
    var isNewTheme = new ThemeDetector().isNewTheme();
    var pageMap = isNewTheme ? pageMapNewTheme : pageMapDefault;

    var button = new RecommendedModulesButton(config.lang, isNewTheme, config.recommendedModulesButtonUrl);

    new Page(pageMap)
      .insertToolbarButton(button);

    if (!isNewTheme) {
      new RecommendedModulesPopinHandler(pageMap, config.recommendedModulesButtonUrl, config.controller)
        .initialize();
    }
  };

})();
