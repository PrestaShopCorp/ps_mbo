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

var mbo = {};

(function() {
  var pageMapDefault = {
    toolbarButtonsContainer: '#toolbar-nav',
    toolbarButtons: '#toolbar-nav > li > a.toolbar_btn',
    toolbarHelpButton: '#toolbar-nav li:last-of-type > a.btn-help',
    toolbarLastElement: '#toolbar-nav li:last-of-type',
    recommendedModulesButton: '#recommended-modules-button',
    fancybox: '.fancybox-quick-view',
    contentContainer: '#content',
    modulesListModal: '#modules_list_container',
    modulesListModalContent: '#modules_list_container_tab_modal',
    modulesListLoader: '#modules_list_loader',
  };

  var pageMapNewTheme = {
    toolbarButtonsContainer: '.toolbar-icons .wrapper',
    toolbarHelpButton: '.toolbar-icons a.btn-help',
    toolbarLastElement: '.toolbar-icons a:last-of-type',
    recommendedModulesButton: '#recommended-modules-button',
    oldButton: '#page-header-desc-configuration-modules-list',
    contentContainer: '#main-div .content-div .container:last',
    modulesListModal: '#modules_list_container',
    modulesListModalContainer: '#main-div .content-div',
    modulesListModalContent: '#modules_list_container_tab_modal',
    modulesListLoader: '#modules_list_loader',
  };

  var decodeHTMLEntities = function(text) {
    var entities = [
        ['amp', '&'],
        ['apos', '\''],
        ['#x27', '\''],
        ['#x2F', '/'],
        ['#39', '\''],
        ['#47', '/'],
        ['lt', '<'],
        ['gt', '>'],
        ['nbsp', ' '],
        ['quot', '"']
    ];

    for (var i = 0, max = entities.length; i < max; ++i) 
        text = text.replace(new RegExp('&'+entities[i][0]+';', 'g'), entities[i][1]);

    return text;
  }

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
     * @param {object} config
     * @param {array} config.translations
     * @param {string} config.recommendedModulesUrl
     * @param {boolean} config.shouldAttachRecommendedModulesAfterContent
     * @param {boolean} config.shouldAttachRecommendedModulesButton
     * @param {boolean} config.shouldUseLegacyTheme
     *
     * @return this
     */
    this.insertRecommendedModules = function(config) {
      if (pageMap.contentContainer) {
        var recommendedModulesRequest = $.ajax({
          type: 'GET',
          dataType: 'json',
          url: config.recommendedModulesUrl,
        });

        recommendedModulesRequest.done(function(data) {
          var recommendedModulesContainer = new RecommendedModulesContainer(config, data.content);

          $(pageMap.contentContainer).append(recommendedModulesContainer.getMarkup());
        });

        recommendedModulesRequest.fail(function(jqXHR, textStatus, errorThrown) {
          var recommendedModulesAlertMessage = new RecommendedModulesAlertMessage(config, 'danger', errorThrown);
          var content = recommendedModulesAlertMessage.getMarkup().get(0).outerHTML;
          if (undefined !== jqXHR.responseJSON && undefined !== jqXHR.responseJSON.content) {
            content += jqXHR.responseJSON.content;
          }
          var recommendedModulesContainer = new RecommendedModulesContainer(config, content);

          $(pageMap.contentContainer).append(recommendedModulesContainer.getMarkup().get(0).outerHTML);
        });
      }

      return this;
    };
  };

  /**
   * Handles markup for the Recommended modules button
   *
   * @param {object} config
   * @param {array} config.translations
   * @param {string} config.recommendedModulesUrl
   * @param {boolean} config.shouldAttachRecommendedModulesAfterContent
   * @param {boolean} config.shouldAttachRecommendedModulesButton
   * @param {boolean} config.shouldUseLegacyTheme
   * @constructor
   */
  var RecommendedModulesButton = function(config) {
    var label = config.translations['Recommended Modules and Services'];
    var buttonId = 'recommended-modules-button';
    var $markup;

    if (config.shouldUseLegacyTheme) {
      $markup = $(
        '<li id="recommended-modules-button-container">\n' +
        '  <a id="' + buttonId + '" class="toolbar_btn pointer mbo-modules-recommended-button " href="' + config.recommendedModulesUrl + '" title="' + label + '">\n' +
        '    <i class="material-icons mi-extension">extension</i>\n' +
        '    <div>' + label + '</div>\n' +
        '  </a>\n' +
        '</li>'
      );
    } else {
      $markup = $(
        '<a class="btn btn-secondary" id="' + buttonId + '" href="' + config.recommendedModulesUrl + '" title="' + label + '">' +
        '<i class="material-icons">extension</i>\n' +
        label +
        '</a>'
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
   * @param {object} config
   * @param {array} config.translations
   * @param {string} config.recommendedModulesUrl
   * @param {boolean} config.shouldAttachRecommendedModulesAfterContent
   * @param {boolean} config.shouldAttachRecommendedModulesButton
   * @param {boolean} config.shouldUseLegacyTheme
   * @param {jQuery|HTMLElement} content
   * @constructor
   */
  var RecommendedModulesContainer = function(config, content) {
    var containerTitle = config.translations['Recommended Modules and Services'];
    var containerDescription = decodeHTMLEntities(config.translations['description']);
    var containerId = 'recommended-modules-container';
    var $markup;

    if (config.shouldUseLegacyTheme) {
      $markup = $(
        '<div class="panel" id="' + containerId + '">\n' +
        '  <h3>\n' +
        '    <i class="icon-puzzle-piece"></i>\n' +
        '    ' + containerTitle + '\n' +
        '  </h3>\n' +
        '  <div class="recommended-modal-content-description">' + containerDescription + '</div>\n' +
        '  <div class="modules_list_container_tab row">\n' +
        
        '    ' + content +'\n' +
        '  </div>\n' +
        '</div>'
      );
    } else {
      $markup = $(
        '<div class="row" id="' + containerId + '">\n' +
        '  <div class="col">\n' +
        '    <div class="card">\n' +
        '      <h3 class="card-header">\n' +
        '        <i class="material-icons">extension</i>\n' +
        '        ' + containerTitle + '\n' +
        '      </h3>\n' +
        '        <div class="recommended-modal-content-description">'+ containerDescription +'</div>\n'+
        '      <div class="card-block">\n' +
        '       ' + content +'\n' +
        '      </div>\n' +
        '    </div>\n' +
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
   * Handles markup for the Recommended modules container
   *
   * @param {object} config
   * @param {array} config.translations
   * @param {string} config.recommendedModulesUrl
   * @param {boolean} config.shouldAttachRecommendedModulesAfterContent
   * @param {boolean} config.shouldAttachRecommendedModulesButton
   * @param {boolean} config.shouldUseLegacyTheme
   * @param {string} type
   * @param {string} text
   * @constructor
   */
  var RecommendedModulesAlertMessage = function(config, type, text) {
    var $markup = $(
      '<div class="alert alert-' + type + '" role="alert">\n' +
      '  <p class="alert-text">\n' +
      '    ' + text + '\n' +
      '  </p>\n' +
      '</div>'
    );

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
   * @param {object} pageMap
   * @param {object} config
   * @param {array} config.translations
   * @param {string} config.recommendedModulesUrl
   * @param {boolean} config.shouldAttachRecommendedModulesAfterContent
   * @param {boolean} config.shouldAttachRecommendedModulesButton
   * @param {boolean} config.shouldUseLegacyTheme
   * @constructor
   */
  var RecommendedModulesModal = function(pageMap, config) {
    var $markup;

    if (!config.shouldUseLegacyTheme) {
      $markup = $(
        '<div id="modules_list_container" class="modal modal-vcenter fade" role="dialog">\n' +
        '  <div class="modal-dialog">\n' +
        '    <div class="modal-content">\n' +
        '      <div class="modal-header">\n' +
        '        <h4 class="modal-title module-modal-title">\n' +
        '          ' + config.translations['Recommended Modules and Services'] + '\n' +
        '        </h4>\n' +
        '        <button type="button" class="close" data-dismiss="modal" aria-label="' + config.translations['Close'] + '">\n' +
        '          <span aria-hidden="true">&times;</span>\n' +
        '        </button>\n' +
        '      </div>\n' +
        '      <div class="modal-body row">\n' +
        '        <div id="modules_list_container_tab_modal" class="col-md-12" style="display:none;"></div>\n' +
        '        <div id="modules_list_loader" class="col-md-12 text-center">\n' +
        '          <button class="btn-primary-reverse onclick unbind spinner"></button>\n' +
        '        </div>\n' +
        '      </div>\n' +
        '    </div>\n' +
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
   * @param {object} pageMap
   * @param {object} config
   * @param {array} config.translations
   * @param {string} config.recommendedModulesUrl
   * @param {boolean} config.shouldAttachRecommendedModulesAfterContent
   * @param {boolean} config.shouldAttachRecommendedModulesButton
   * @param {boolean} config.shouldUseLegacyTheme
   * @constructor
   */
  var RecommendedModulesPopinHandler = function(pageMap, config) {

    var initPopin = function() {
      if (config.shouldUseLegacyTheme) {
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
        $(pageMap.modulesListModal).find('h3.modal-title').text(config.translations['Recommended Modules and Services']);
      } else {
        if (!$(pageMap.modulesListModal).length) {
          var modal = new RecommendedModulesModal(pageMap, config);
          $(pageMap.modulesListModalContainer).append(modal.getMarkup().get(0).outerHTML);
        }
      }
    };

    var openModulesList = function() {
      var recommendedModulesRequest = $.ajax({
        type: 'GET',
        dataType: 'json',
        url: config.recommendedModulesUrl,
      });

      $(pageMap.modulesListModal).modal('show');

      recommendedModulesRequest.done(function (data) {
        var descriptionHtml = decodeHTMLEntities(config.translations['description']);
        $(pageMap.modulesListModalContent).html('<div class="recommended-modal-description">' + descriptionHtml  + '</div>' + data.content).slideDown();
        $(pageMap.modulesListLoader).hide();
      });

      recommendedModulesRequest.fail(function(jqXHR, textStatus, errorThrown) {
        var recommendedModulesAlertMessage = new RecommendedModulesAlertMessage(config, 'danger', errorThrown);
        var content = recommendedModulesAlertMessage.getMarkup().get(0).outerHTML;
        if (undefined !== jqXHR.responseJSON && undefined !== jqXHR.responseJSON.content) {
          content += jqXHR.responseJSON.content;
        }

        $(pageMap.modulesListModalContent).html(content).slideDown();
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
   * @param {array} config.translations
   * @param {string} config.recommendedModulesUrl
   * @param {boolean} config.shouldAttachRecommendedModulesAfterContent
   * @param {boolean} config.shouldAttachRecommendedModulesButton
   * @param {boolean} config.shouldUseLegacyTheme
   */
  mbo.initialize = function(config) {
    var pageMap = config.shouldUseLegacyTheme ? pageMapDefault : pageMapNewTheme;
    var page = new Page(pageMap);

    page.removeOldButton();

    if (config.shouldAttachRecommendedModulesButton) {
      var button = new RecommendedModulesButton(config);
      var recommendedModulesPopinHandler = new RecommendedModulesPopinHandler(pageMap, config);
      page.insertToolbarButton(button);
      recommendedModulesPopinHandler.initialize();
    }

    if (config.shouldAttachRecommendedModulesAfterContent) {
      page.insertRecommendedModules(config);
    }
  };

})();
