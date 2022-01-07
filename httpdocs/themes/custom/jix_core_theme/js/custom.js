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

      if (settings.site?.stats_url) {
        fetchStatistics().then((data) => {
          const stats = JSON.parse(data);
          const jobsCount = Number(stats.totalJobs).toLocaleString();
          const applicationsCount = Number(stats.totalApplications).toLocaleString();
          $(context).find('#jobs-count > span').html(jobsCount);
          $(context).find('#applications-count > span').html(applicationsCount);
        })
      }

      if(settings.path.isFront) {
        $(context).find('nav#block-jix-core-theme-main-menu > ul.nav > li:first-child').addClass('active');
        $(context).find('nav#block-jix-core-theme-main-menu > ul.nav > li:first-child > a').addClass('active');
      }

      $(context).find('table').removeAttr('style').removeClass().addClass('table table-hover table-bordered');
      $(context).find('table p').addClass('m-0');
      $(context).find('th').removeAttr('style class');
      $(context).find('td').removeAttr('style class');

      $(context).find('a#edit-delete').addClass('btn btn-danger btn-sm');

      $(context).find('div.employer-description').readmore({collapsedHeight: 100});

      $(context).find('fieldset#grp-social-media > div').addClass('border border-1 p-2 rounded-2')

      const jobCategorySelect = $(context).find('select#edit-field-job-category');
      const selectTitle = Drupal.t('Select category');
      jobCategorySelect.select2({
        multiple: true,
        width: '100%',
        theme: 'bootstrap',
        placeholder: {
          id: '_none',
          text: selectTitle
        },
      });
      jobCategorySelect.change(function () {
        $(context).find('li.select2-selection__choice[title="- None -"]').hide();
      });

      const employerSectorSelect = $(context).find('select#edit-field-employer-sector');
      const sectorTitle = Drupal.t('Choose sector');
      employerSectorSelect.select2({
        multiple: true,
        width: '100%',
        theme: 'bootstrap',
        placeholder: {
          id: '_none',
          text: sectorTitle
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

    }
  };

  async function fetchStatistics(args) {
    let result;

    try {
      result = await $.ajax({
        url: 'https://search.jobinrwanda.com/api/stats',
        type: 'GET',
        data: args
      });
      return result;
    } catch (error) {
      console.error(error);
    }
  }

})(jQuery, Drupal);

