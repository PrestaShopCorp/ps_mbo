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

var mboDashboardWidget = {};

(function() {
  var pageMapDefault = {
    widgetContainer: '#psaddonsconnect-widget-container',
    widgetButtonClose: '#psaddonsconnect-widget-close',
    adviceContentContainer: '#psaddonsconnect-tips-content-container',
    adviceLoaderContainer: '#psaddonsconnect-tips-loader-container',
  };

  /**
   * Handles markup for the Recommended modules button
   *
   * @param {object} config
   * @param {object} config.translations - Object containing translations
   * @param {object} data
   * @param {string} data.advice
   * @param {string} data.link
   * @constructor
   */
  var WeekAdvice = function(config, data) {
    var $markup = $(
      '<div>' +
      '  <p>' +
      '    <i class="icon-lightbulb"></i>\n' +
      '    ' + data.advice + '\n' +
      '  </p>' +
      '  <a href="' + data.link + '" target="_blank" class="btn btn-default pull-right">\n' +
      '    <i class="icon-external-link"></i>\n' +
      '    ' + config.translations['See the entire selection'] + '\n' +
      '  </a>\n' +
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
   * @param {object} config
   * @param {object} config.translations - Object containing translations
   * @param {string} type
   * @param {string} text
   * @constructor
   */
  var WeekAdviceAlertMessage = function(config, type, text) {
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
   * Handles page interactions
   *
   * @param {object} pageMap
   * @constructor
   */
  var Page = function(pageMap) {
    /**
     * Inserts the recommended modules in the DOM
     *
     * @param {object} config
     * @param {object} config.translations - Object containing translations
     * @param {string} config.weekAdviceUrl
     *
     * @return this
     */
    this.displayAdvice = function(config) {
      if (pageMap.adviceContentContainer) {
        var weekAdviceRequest = $.ajax({
          type: 'GET',
          dataType: 'json',
          url: config.weekAdviceUrl,
        });

        weekAdviceRequest.done(function(data) {
          var content = '';
          if (undefined === data.content || null === data.content) {
            content = config.translations['No tip available today.'];
          } else {
            var weekAdvice = new WeekAdvice(config, data.content);
            content = weekAdvice.getMarkup().get(0).outerHTML;
          }

          $(pageMap.adviceContentContainer).html(content).slideDown();
          $(pageMap.adviceLoaderContainer).hide();
        });

        weekAdviceRequest.fail(function(jqXHR, textStatus, errorThrown) {
          var weekAdviceAlertMessage = new WeekAdviceAlertMessage(config, 'danger', errorThrown);

          $(pageMap.adviceContentContainer).html(weekAdviceAlertMessage.getMarkup().get(0).outerHTML).slideDown();
          $(pageMap.adviceLoaderContainer).hide();
        });
      }

      return this;
    };
  };

  /**
   * Inserts the recommended modules button in the toolbar
   *
   * @param {object} config
   * @param {object} config.translations
   * @param {string} config.weekAdviceUrl
   */
  mboDashboardWidget.initialize = function(config) {
    $(document).on('click', pageMapDefault.widgetButtonClose, function(event) {
      event.preventDefault();
      $(pageMapDefault.widgetContainer).hide();
    });

    if (0 === $(pageMapDefault.adviceContentContainer).children().length) {
      var page = new Page(pageMapDefault);
      page.displayAdvice(config);
    }
  };
})();
