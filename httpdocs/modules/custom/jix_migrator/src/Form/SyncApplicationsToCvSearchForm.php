<?php

namespace Drupal\jix_migrator\Form;

use Drupal;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\jix_notifier\Form\NotifierGeneralSettingsForm;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionInterface;
use GuzzleHttp\Exception\ClientException;
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
    $channel = 'jix_migrator';
    if ($item instanceof WebformSubmissionInterface) {
      $applicationsService = Drupal::service('jix_notifier.job_applications_service');
      $data = $applicationsService->getCvSearchJsonData($item);
      try {
        $response = Drupal::httpClient()->post(self::cvSearchUrl(), ['json' => $data]);
        Drupal::logger($channel)->debug(Drupal\Component\Serialization\Json::encode(['request' => ['json' => $data], 'response' => $response]));
        if ($response->getStatusCode() == 200) {
          try {
            $item->setElementData('field_application_sync', 'Yes');
            $item->save();
          } catch (EntityStorageException $e) {
            Drupal::logger($channel)->error('Saving application failed: ' . $e->getMessage());
          }
        } else {
          Drupal::logger($channel)->error(t('Synchronizing application @id failed with error code @code: @message',
            [
              '@id' => $item->id(),
              '@code' => $response->getStatusCode(),
              '@message' => $response->getReasonPhrase()
            ]));
        }
      } catch (ClientException $e) {
        Drupal::logger($channel)->error('Cv Search Client Exception: ' . $e->getMessage());
      }

      $context['results'][] = $item;
      $context['message'] = t('Synchronised @count applications', array('@count' => count($context['results'])));
    }
  }
}
