/**
 * @file
 * Global utilities.
 *
 */
(function ($, Drupal) {

  'use strict';

  Drupal.webform.intlTelInput = Drupal.webform.intlTelInput || {};
  Drupal.webform.select2 = Drupal.webform.select2 || {};

  Drupal.behaviors.jix_core_theme = {
    attach: function (context, settings) {
      const isMobile = Modernizr.mq('(max-width: 767.98px)');
      if (isMobile) {
        $(context).find('form#views-exposed-form-jobs-display-page-search-result-page > div.row > div').addClass('mb-2');
        $(context).find('nav#block-jobstabsmenu > ul').addClass('border border-light rounded p-2');
      }

      $(context).find('div.employer-description').readmore({collapsedHeight: 100});

      const jobCategorySelect = $(context).find('select#edit-field-job-category');
      jobCategorySelect.select2({
        multiple: true,
        width: '100%',
        theme: 'bootstrap',
        placeholder: {
          id: '_none',
          text: 'Select category'
        },
      });
      jobCategorySelect.change(function () {
        $(context).find('li.select2-selection__choice[title="- None -"]').hide();
      });

      const employerSectorSelect = $(context).find('select#edit-field-employer-sector');
      employerSectorSelect.select2({
        multiple: true,
        width: '100%',
        theme: 'bootstrap',
        placeholder: {
          id: '_none',
          text: 'Choose sector'
        },
      });
      employerSectorSelect.change(function () {
        $(context).find('li.select2-selection__choice[title="- None -"]').hide();
      });

      $(context).find('input.select2-search__field').addClass('w-100');

      $(context).find('input.form-tel').each(function () {
        if (settings.site?.target_country !== undefined) {
          const countryCode = settings.site.target_country.toLowerCase();
          $(this).intlTelInput({initialCountry: countryCode, nationalMode: false});
        } else {
          $(this).intlTelInput({nationalMode: false});
        }
      });

      if(settings.path.isFront) {
        $(context).find('ul.nav[block="block-jix-core-theme-main-menu"] > li:first-child').addClass('active');
      }
    }
  };

})(jQuery, Drupal);
