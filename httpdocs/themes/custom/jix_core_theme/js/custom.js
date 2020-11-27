/**
 * @file
 * Global utilities.
 *
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.jix_core_theme = {
    attach: function (context, settings) {
      const isMobile = Modernizr.mq('(max-width: 767.98px)');
      if (isMobile) {
        $(context).find('form#views-exposed-form-jobs-display-page-search-result-page > div.row > div').addClass('mb-2');
      }
    }
  };

})(jQuery, Drupal);
