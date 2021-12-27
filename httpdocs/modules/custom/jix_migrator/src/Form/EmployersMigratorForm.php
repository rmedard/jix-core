<?php


namespace Drupal\jix_migrator\Form;


use Drupal;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\node\Entity\Node;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Provides a form for deleting a batch_import_example entity.
 *
 * @ingroup batch_import_employers
 */
class EmployersMigratorForm extends FormBase
{

  private $channel;
  private $url;
  private $client;

  public function __construct()
  {
    $this->channel = 'jix_migrator';
    $this->url = 'https://www.jobinrwanda.com/jirapi/node.json';
    $this->client = Drupal::httpClient();
  }

  public function getFormId(): string
  {
    return 'batch_migrator_form';
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
      'title' => t('Importing employers'),
      'operations' => [],
      'init_message' => t('Import employers is starting.'),
      'progress_message' => t('Processed @current out of @total. Estimated time: @estimate.'),
      'error_message' => t('The process has encountered an error.')
    ];

    $pagesCount = 115;

    for ($i = 0; $i <= $pagesCount; $i++) {
      try {
        $response = $this->client->request('GET', $this->url,
          [
            'auth' => ['admin', 'mypass@jir5'],
            'query' => ['parameters[type]' => 'employer', 'parameters[status]' => 1, 'pagesize' => 100, 'page' => $i]
          ]);
        $batch['operations'][] = [['\Drupal\jix_migrator\Form\EmployersMigratorForm', 'process'], [$response->getBody()->getContents()]];
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
      $employerData = $client->request('GET', $node['uri'], ['auth' => ['admin', 'mypass@jir5']]);

      $employer = Json::decode($employerData->getBody());

      $savedFile = null;
      if (!empty($employer['field_employer_logo'])) {
        $localUri = $employer['field_employer_logo']['und'][0]['uri'];
        $data = file_get_contents('https://www.jobinrwanda.com/sites/default/files/poster_company_logo' . substr($localUri, strrpos($localUri, '/'))); //"public://poster_company_logo/logo_15099.png"
        $file = file_save_data($data, 'public://employer_logos/' .
          $employer['field_employer_logo']['und'][0]['filename'], FileSystemInterface::EXISTS_REPLACE);
        $savedFile = File::create([
          'uid' => 1,
          'filename' => $file->getFilename(),
          'uri' => $file->getFileUri(),
          'status' => 1
        ]);
        try {
          $savedFile->save();
        } catch (EntityStorageException $e) {
          Drupal::logger($channel)->error('Saving file failed: ' . $e->getMessage());
        }
      }

      $termIds = [];
      if (!empty($employer['field_category'])) {
        $tnames = [];
        foreach ($employer['field_category']['und'] as $tid) {
          try {
            $termResponse = $client->request('GET', $termUrl . $tid['tid'], ['auth' => ['admin', 'mypass@jir5']]);
            $remoteTerm = Json::decode($termResponse->getBody());
            $tnames[] = $remoteTerm['name'];
          } catch (GuzzleException $e) {
            Drupal::logger($channel)->error('Guzzle exception: ' . $e->getMessage());
          }
        }

        $query = Drupal::entityQuery('taxonomy_term')
          ->condition('vid', 'category')
          ->condition('name', $tnames, 'IN');
        $termIds = $query->execute();
      }

      $employerWebsite = [];
      if (!empty($employer['field_employer_website'])) {
        $link = $employer['field_employer_website']['und'][0]['url'];
        if (str_starts_with($link, "http")) {
          $employerWebsite = $link;
        } else {
          $employerWebsite = 'https://' . $link;
        }
      }

      try {
        Node::create([
          'type' => $employer['type'],
          'field_old_nid' => $employer['nid'],
          'title' => $employer['title'],
          'status' => 1,
          'field_employer_client_reference' => empty($employer['field_employer_client_reference']) ? [] : $employer['field_employer_client_reference']['und'][0]['value'],
          'field_employer_email' => empty($employer['field_employer_email']) ? [] : $employer['field_employer_email']['und'][0]['email'],
          'field_employer_facebook' => empty($employer['field_employer_facebook']) ? [] : $employer['field_employer_facebook']['und'][0]['url'],
          'field_employer_linkedin' => empty($employer['field_employer_linkedin']) ? [] : $employer['field_employer_linkedin']['und'][0]['url'],
          'field_employer_twitter' => empty($employer['field_employer_twitter']) ? [] : $employer['field_employer_twitter']['und'][0]['url'],
          'field_employer_featured' => empty($employer['field_featured_employer']) ? [] : $employer['field_featured_employer']['und'][0]['value'] == "1",
          'field_employer_public_service' => empty($employer['field_employer_public_employer']) ? [] : $employer['field_employer_public_employer']['und'][0]['value'] == "1",
          'field_employer_sent2dwh' => true,
          'field_employer_summary' => ['value' => $employer['field_summary']['und'][0]['safe_value'], 'format' => 'full_html'],
          'field_employer_tin_number' => empty($employer['field_tax_identification_number']) ? [] : $employer['field_tax_identification_number']['und'][0]['value'],
          'field_employer_telephone' => empty($employer['field_employer_telephone']) ? [] : $employer['field_employer_telephone']['und'][0]['value'],
          'field_employer_website' => $employerWebsite,
          'field_employer_logo' => $savedFile instanceof FileInterface ? $savedFile : [],
          'field_employer_sector' => $termIds
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
