<?php

namespace Drupal\jix_migrator\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\jix_notifier\Form\NotifierGeneralSettingsForm;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionInterface;
use PDO;

class SyncApplicationsToCvSearchForm extends FormBase
{

  private static function cvSearchUrl()
  {
    return Drupal::config(NotifierGeneralSettingsForm::SETTINGS)->get('cv_search_url');
  }

  public function getFormId(): string
  {
    return 'sync_applications_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $form['actions'] = array(
      '#type' => 'actions',
      'submit' => array(
        '#type' => 'submit',
        '#value' => 'Proceed',
      ),
    );

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $batch = [
      'title' => t('Synchronizing applications'),
      'operations' => [],
      'init_message' => t('Synchronizing applications is starting.'),
      'progress_message' => t('Processed @current out of @total. Estimated time: @estimate.'),
      'error_message' => t('The process has encountered an error.')
    ];
    $count = 0;
    if (!empty(self::cvSearchUrl())) {
      $applications = Drupal::database()
        ->select('webform_submission_data', 'wsd')
        ->fields('wsd', array('sid'))
        ->condition('wsd.webform_id', 'default_job_application_form')
        ->condition('wsd.name', 'field_application_sync')
        ->condition('wsd.value', 'No')
        ->execute()
        ->fetchAll(PDO::FETCH_COLUMN);

      foreach ($applications as $application) {
        $application = WebformSubmission::load(intval($application));
        $batch['operations'][] = [['\Drupal\jix_migrator\Form\SyncApplicationsToCvSearchForm', 'sync'], [$application]];
        ++$count;
      }
    }
    batch_set($batch);
    $form_state->setRebuild(TRUE);
    Drupal::messenger()->addMessage('Synchronized ' . $count . ' applications!');
  }

  public static function sync($item, &$context)
  {
    if ($item instanceof WebformSubmissionInterface) {
      $applicationsService = Drupal::service('jix_notifier.job_applications_service');
      $applicationsService->sendToCvSearch($item, self::cvSearchUrl());
      $context['results'][] = $item;
      $context['message'] = t('Synchronised @count applications', array('@count' => count($context['results'])));
    }
  }
}
