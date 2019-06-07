'use strict';
/**
 * 2007-2019 PrestaShop SA and Contributors
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
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

var mbo = {};

(function() {
  var pageMapDefault = {
    toolbarButtonsContainer: '#toolbar-nav',
    toolbarButtons: '#toolbar-nav > li > a.toolbar_btn',
    toolbarHelpButton: '#toolbar-nav li:last-of-type > a.btn-help',
    toolbarLastElement: '#toolbar-nav li:last-of-type',
    recommendedModulesButton: '#recommended-modules-button',
    fancybox: '.fancybox-quick-view',
    modulesListModal: '#modules_list_container',
    modulesListModalContent: '#modules_list_container_tab_modal',
    modulesListLoader: '#modules_list_loader',
    contentContainer: '#content',
  };

  var pageMapNewTheme = {
    toolbarButtonsContainer: '.toolbar-icons .wrapper',
    toolbarHelpButton: '.toolbar-icons a.btn-help',
    toolbarLastElement: '.toolbar-icons a:last-of-type',
    recommendedModulesButton: '#recommended-modules-button',
    oldButton: '#page-header-desc-configuration-modules-list',
    contentContainer: '#main-div .content-div .container:last',
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
     * Remove core-generated "recommended module" button in PS < 1.7.6.0
     * @return this
     */
    this.removeOldButton = function() {
      if (pageMap.toolbarButtons) {
        // default theme
        $(pageMap.toolbarButtons).filter(
          function() {
            var buttonIdPattern = /^page-header-desc-[a-z-_]+-modules-list$/;
            return String($(this).attr('id'))
              .match(buttonIdPattern);
          }
        ).parent().remove();
      }

      if (pageMap.oldButton) {
        // new theme
        $(pageMap.oldButton).remove();
      }

      return this;
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

    /**
     * Inserts the recommended modules in the DOM
     *
     * @param {string} recommendedModulesAjaxUrl
     * @param {string} currentControllerName
     * @param {string} recommendedModules
     * @param {string} source
     *
     * @return this
     */
    this.insertRecommendedModules = function(recommendedModulesAjaxUrl, currentControllerName, recommendedModules, source) {
      if (pageMap.contentContainer) {
        var recommendedModulesRequest = $.ajax({
          type: 'GET',
          dataType: 'html',
          url: recommendedModulesAjaxUrl,
          data: {
            ajax : "1",
            controller : currentControllerName,
            action : "getTabModulesList",
            tab_modules_list : recommendedModules,
            back_tab_modules_list : window.location.href,
            admin_list_from_source : source
          }
        });

        recommendedModulesRequest.done(function(data) {
          $(pageMap.contentContainer).append(data);
        });
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
   * Handles markup for the Recommended modules container
   *
   * @param {object} trad - Translations dictionary
   * @param {boolean} isNewTheme
   * @param {string} content
   * @constructor
   */
  var RecommendedModulesContainer = function(trad, isNewTheme, content) {
    var containerTitle = trad['Recommended Modules and Services'];
    var containerId = 'recommended-modules-container';
    var $markup;

    if (isNewTheme) {
      $markup = $(
        '<div class="row" id="' + containerId + '">\n' +
        '  <div class="card">\n' +
        '    <h3 class="card-header">\n' +
        '      <i class="material-icons">extension</i>\n' +
        '      ' + containerTitle + '\n' +
        '    </h3>\n' +
        '    <div class="card-block">\n' +
        '      ' + content +'\n' +
        '    </div>\n' +
        '  </div>\n' +
        '</div>'
      );
    } else {
      $markup = $(
        '<div class="panel" id="' + containerId + '">\n' +
        '  <h3>\n' +
        '    <i class="icon-list-ul"></i>\n' +
        '    ' + containerTitle + '\n' +
        '  </h3>\n' +
        '  <div class="modules_list_container_tab row">\n' +
        '    ' + content +'\n' +
        '  </div>\n' +
        '</div>'
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
   * @param {string} recommendedModulesAjaxUrl
   * @param {string} currentControllerName
   * @param {string} recommendedModules
   * @param {string} source
   * @constructor
   */
  var RecommendedModulesPopinHandler = function(pageMap, recommendedModulesAjaxUrl, currentControllerName, recommendedModules, source) {

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

      var recommendedModulesRequest = $.ajax({
        type: 'GET',
        dataType: 'html',
        url: recommendedModulesAjaxUrl,
        data: {
          ajax : "1",
          controller : currentControllerName,
          action : "getTabModulesList",
          tab_modules_list : recommendedModules,
          back_tab_modules_list : window.location.href,
          admin_list_from_source : source
        }
      });

      recommendedModulesRequest.done(function(data) {
        $(pageMap.modulesListModalContent).html(data).slideDown();
        $(pageMap.modulesListLoader).hide();
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
   * @param {string} config.recommendedModulesAjaxUrl - URL for button
   * @param {string} config.controller - Current controller name
   * @param {string} config.recommendedModules - Current controller name
   * @param {string} config.source - Current controller name
   */
  mbo.insertToolbarButton = function(config) {
    var isNewTheme = new ThemeDetector().isNewTheme();
    var pageMap = isNewTheme ? pageMapNewTheme : pageMapDefault;

    var button = new RecommendedModulesButton(config.lang, isNewTheme, config.recommendedModulesButtonUrl);

    new Page(pageMap)
      .removeOldButton()
      .insertToolbarButton(button);

    if (!isNewTheme) {
      new RecommendedModulesPopinHandler(pageMap, config.recommendedModulesAjaxUrl, config.controller, config.recommendedModules, config.source)
        .initialize();
    }
  };

  /**
   * Inserts the recommended modules button in the toolbar
   *
   * @param {object} config
   * @param {object} config.lang - Object containing translations
   * @param {string} config.recommendedModulesButtonUrl - URL for button
   * @param {string} config.recommendedModulesAjaxUrl - URL for button
   * @param {string} config.controller - Current controller name
   * @param {string} config.recommendedModules - Current controller name
   * @param {string} config.source - Current controller name
   */
  mbo.insertRecommendedModules = function(config) {
    var isNewTheme = new ThemeDetector().isNewTheme();
    var pageMap = isNewTheme ? pageMapNewTheme : pageMapDefault;

    new Page(pageMap)
      .removeOldButton()
      .insertRecommendedModules(config.recommendedModulesAjaxUrl, config.controller, config.recommendedModules, config.source);
  };

})();
