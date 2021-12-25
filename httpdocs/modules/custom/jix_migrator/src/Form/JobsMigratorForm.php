<?php


namespace Drupal\jix_migrator\Form;


use Drupal;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Provides a form for deleting a batch_import_example entity.
 *
 * @ingroup batch_import_jobs
 */
class JobsMigratorForm extends FormBase
{

  private string $channel;
  private string $url;
  private Client $client;

  public function __construct()
  {
    $this->channel = 'jix_migrator';
    $this->url = 'https://www.jobinrwanda.com/jirapi/node.json';
    $this->client = Drupal::httpClient();
  }

  public function getFormId(): string
  {
    return 'batch_jobs_migrator_form';
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
      'title' => t('Importing news'),
      'operations' => [],
      'init_message' => t('Import jobs is starting.'),
      'progress_message' => t('Processed @current out of @total. Estimated time: @estimate.'),
      'error_message' => t('The process has encountered an error.')
    ];

    $pagesCount = 2;

    for ($i = 0; $i < $pagesCount; $i++) {
      try {
        $response = $this->client->request('GET', $this->url,
          [
            'auth' => ['admin', 'mypass@jir5'],
            'query' => ['parameters[type]' => 'job', 'parameters[status]' => 1, 'pagesize' => 100, 'page' => $i]
          ]);
        $batch['operations'][] = [['\Drupal\jix_migrator\Form\JobsMigratorForm', 'process'], [$response->getBody()->getContents()]];
      } catch (GuzzleException $e) {
        Drupal::logger($this->channel)->error('Guzzle exception: ' . $e->getMessage());
      }
    }

    batch_set($batch);
    Drupal::messenger()->addMessage('Imported ' . $pagesCount . ' pages!');

    $form_state->setRebuild(TRUE);
  }

  public static function process($item, &$context)
  {
    $processed = 0;
    $termUrl = "https://www.jobinrwanda.com/jirapi/taxonomy_term/";
    $client = Drupal::httpClient();
    $channel = 'jix_migrator';
    $nodes = Json::decode($item);
    Drupal::logger($channel)->info('Fetched Nodes: ' . count($nodes));
    foreach ($nodes as $node) {
      $jobData = $client->request('GET', $node['uri'], ['auth' => ['admin', 'mypass@jir5']]);

      $job = Json::decode($jobData->getBody());

      $attachmentFile = [];
      if (!empty($job['field_attachment'])) {
        $localUri = $job['field_attachment']['und'][0]['uri'];
        $filename = preg_replace("/\s+/", "", $job['field_attachment']['und'][0]['filename']);
        $data = file_get_contents(str_replace(' ', '%20', 'https://www.jobinrwanda.com/sites/default/files/job details files' . substr($localUri, strrpos($localUri, '/'))));
        $file = file_save_data($data, 'public://job_description_files/' .
          $filename, FileSystemInterface::EXISTS_REPLACE);
        $attachmentFile = File::create([
          'uid' => 1,
          'filename' => $file->getFilename(),
          'uri' => $file->getFileUri(),
          'status' => 1
        ]);
        try {
          $attachmentFile->save();
        } catch (EntityStorageException $e) {
          Drupal::logger($channel)->error('Saving file failed: ' . $e->getMessage());
        }
      }

      $categoryTermIds = [];
      if (!empty($job['field_category'])) {
        $tNames = [];
        foreach ($job['field_category']['und'] as $tid) {
          try {
            $termResponse = $client->request('GET', $termUrl . $tid['tid'], ['auth' => ['admin', 'mypass@jir5']]);
            $remoteTerm = Json::decode($termResponse->getBody());
            $tNames[] = $remoteTerm['name'];
          } catch (GuzzleException $e) {
            Drupal::logger($channel)->error('Guzzle exception: ' . $e->getMessage());
          }
        }

        $query = Drupal::entityQuery('taxonomy_term')
          ->condition('vid', 'category')
          ->condition('name', $tNames, 'IN');
        $categoryTermIds = $query->execute();
      }

      $experience = [];
      if (!empty($job['field_desired_experience'])) {
        $expTermId = intval($job['field_desired_experience']['und'][0]['tid']);
        switch ($expTermId) {
          case 43:
            $experience = [58];
            break;
          case 44:
            $experience = [59];
            break;
          case 45:
            $experience = [60];
            break;
          case 46:
            $experience = [62];
            break;
          case 47:
            $experience = [61];
            break;
          case 48:
            $experience = [56];
            break;
          case 49:
            $experience = [57];
            break;
          case 126:
            $experience = [63];
            break;
        }
      }

      $education = [];
      if (!empty($job['field_desired_education_level'])) {
        $edTermId = intval($job['field_desired_education_level']['und'][0]['tid']);
        switch ($edTermId) {
          case 37:
            $education = [64];
            break;
          case 38:
            $education = [65];
            break;
          case 39:
            $education = [66];
            break;
          case 40:
            $education = [67];
            break;
          case 41:
            $education = [70];
            break;
          case 42:
            $education = [71];
            break;
          case 125:
            $education = [72];
            break;
          case 137:
            $education = [68];
            break;
          case 138:
            $education = [69];
            break;
        }
      }

      $contractType = [];
      if (!empty($job['field_contrat_type'])) {
        $contTermId = intval($job['field_contrat_type']['und'][0]['tid']);
        switch ($contTermId) {
          case 92:
            $contractType = 'internship';
            break;
          case 93:
            $contractType = 'part_time';
            break;
          case 94:
            $contractType = 'full_time';
            break;
          case 95:
            $contractType = 'contract';
            break;
          case 96:
            $contractType = 'temporary';
            break;
          case 97:
            $contractType = 'tender';
            break;
          case 98:
            $contractType = 'other';
            break;
          case 163:
            $contractType = 'freelance';
            break;
        }
      }

      $howToApply = [];
      if (!empty($job['field_application_form_type'])) {
        $howTermId = intval($job['field_application_form_type']['und'][0]['tid']);
        $howToApply = match ($howTermId) {
          26 => 'email',
          28 => 'external_link',
          default => 'no_online_app',
        };
      }

      $postingPlan = [];
      if (!empty($job['field_posting_type'])) {
        $postTermId = intval($job['field_posting_type']['und'][0]['tid']);
        switch ($postTermId) {
          case 32:
            $postingPlan = 'standard';
            break;
          case 33:
            $postingPlan = 'featured';
            break;
          case 34:
            $postingPlan = 'featured_custom';
            break;
          case 36:
            $postingPlan = 'featured_shortlist';
            break;
        }
      }

      $query = Drupal::entityQuery('node')
        ->condition('type', 'employer')
        ->condition('field_old_nid', intval($job['field_employer']['und'][0]['target_id']));
      $employerIds = $query->execute();

      $externalLink = [];
      if (!empty($job['field_external_application_link'])) {
        $link = $job['field_external_application_link']['und'][0]['url'];
        if (str_starts_with($link, "http")) {
          $externalLink = $link;
        } else {
          $externalLink = 'https://' . $link;
        }
      }

      try {
        Node::create([
          'type' => 'job',
          'field_job_old_nid' => $job['nid'],
          'title' => $job['title'],
          'uid' => 1,
          'status' => 1,
          'field_job_additional_email' => empty($job['field_additional_email_where_to']) ? [] : $job['field_additional_email_where_to']['und'][0]['email'],
          'field_job_offer_type' => lcfirst($job['field_offer_type']['und'][0]['value']),
          'field_job_application_deadline' => str_replace(' ', 'T', $job['field_deadline_for_application']['und'][0]['value']),
          'field_job_attachment' => $attachmentFile,
          'field_job_category' => $categoryTermIds,
          'field_job_city' => empty($job['field_job_city']) ? [] : $job['field_job_city']['und'][0]['value'],
          'field_job_employer' => ['target_id' => intval(reset($employerIds))],
          'field_job_contact_email' => empty($job['field_contact_email']) ? [] : $job['field_contact_email']['und'][0]['email'],
          'field_job_contact_name' => empty($job['field_contact_name']) ? [] : $job['field_contact_name']['und'][0]['value'],
          'field_job_contact_phone' => empty($job['field_contact_phone_number']) ? [] : $job['field_contact_phone_number']['und'][0]['value'],
          'field_job_contract_type' => $contractType,
          'field_job_country' => empty($job['field_job_country']) ? [] : $job['field_job_country']['und'][0]['iso2'],
          'field_job_desired_experience' => $experience,
          'field_job_education_level' => $education,
          'field_job_application_email' => empty($job['field_email_where_to_send_applic']) ? [] : $job['field_email_where_to_send_applic']['und'][0]['email'],
          'field_job_ext_application_link' => $externalLink,
          'field_job_full_description' => ['value' => $job['body']['und'][0]['safe_value'], 'format' => 'full_html'],
          'field_job_how_to_apply' => $howToApply,
          'field_job_invoice_reference' => empty($job['field_invoice_reference']) ? [] : $job['field_invoice_reference']['und'][0]['value'],
          'field_job_creator_location' => [],
          'field_job_number_of_positions' => empty($job['field_number_of_positions']) ? [] : $job['field_number_of_positions']['und'][0]['value'],
          'field_job_posting_plan' => $postingPlan,
          'field_job_repost_date' => empty($job['field_job_activation_date']) ? [] : str_replace(' ', 'T', $job['field_job_activation_date']['und'][0]['value']),
          'field_job_sent_in_sms' => 1,
          'field_job_sent2dwh' => 1,
          'field_job_super_featured' => empty($job['field_job_super_featured']) ? 0 : $job['field_job_super_featured']['und'][0]['value'] == "1"
        ])->save();
      } catch (EntityStorageException $e) {
        Drupal::logger($channel)->error('Saving node failed: ' . $e->getMessage());
      }
      $processed += 1;
    }

    $context['results'][] = $item;
    $context['message'] = t('Processed @count', array('@count' => $processed));
  }
}
