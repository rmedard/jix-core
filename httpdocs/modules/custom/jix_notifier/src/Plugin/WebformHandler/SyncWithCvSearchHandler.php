<?php

namespace Drupal\jix_notifier\Plugin\WebformHandler;

use Drupal;
use Drupal\Component\Utility\UrlHelper;
use Drupal\jix_notifier\Form\NotifierGeneralSettingsForm;
use Drupal\webform\Annotation\WebformHandler;
use Drupal\webform\Plugin\WebformHandler\RemotePostWebformHandler;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Synchronise job submission with Cv Search
 *
 * @WebformHandler(
 *   id = "Send job application to CV Search",
 *   label= @Translation("Send job application to CV Search"),
 *   category= @Translation("Job Application Creation"),
 *   description= @Translation("Sends job application to Cv Search Engine"),
 *   cardinality= \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 *   )
 */
class SyncWithCvSearchHandler extends RemotePostWebformHandler
{
  /**
   * @inheritdoc
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE)
  {
    $synced = $webform_submission->getData()['field_application_sync'];
    if ($webform_submission->getState() === WebformSubmissionInterface::STATE_COMPLETED && $synced !== 'Yes') {
      $cvSearchUrl = trim(Drupal::config(NotifierGeneralSettingsForm::SETTINGS)->get('cv_search_url'));
      if (!empty($cvSearchUrl)) {
        if (UrlHelper::isValid($cvSearchUrl)) {
          $applicationsService = Drupal::service('jix_notifier.job_applications_service');
          $applicationsService->sendToCvSearch($webform_submission, $cvSearchUrl);
        } else {
          Drupal::logger("jix_notifier")->error('Invalid CV Search Url: ' . $cvSearchUrl);
        }
      }
    }
  }
}
