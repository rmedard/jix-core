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
 * @ingroup batch_import_news
 */
class NewsMigratorForm extends FormBase
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
    return 'batch_news_migrator_form';
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
      'init_message' => t('Import news is starting.'),
      'progress_message' => t('Processed @current out of @total. Estimated time: @estimate.'),
      'error_message' => t('The process has encountered an error.')
    ];

    $pagesCount = 2;

    for ($i = 0; $i <= $pagesCount; $i++) {
      try {
        $response = $this->client->request('GET', $this->url,
          [
            'auth' => ['admin', 'mypass@jir5'],
            'query' => ['parameters[type]' => 'news', 'parameters[status]' => 1, 'pagesize' => 50, 'page' => $i]
          ]);
        $batch['operations'][] = [['\Drupal\jix_migrator\Form\NewsMigratorForm', 'process'], [$response->getBody()->getContents()]];
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
    $client = Drupal::httpClient();
    $channel = 'jix_migrator';
    $nodes = Json::decode($item);
    Drupal::logger($channel)->info('Fetched Nodes: ' . count($nodes));
    foreach ($nodes as $node) {
      $newsData = $client->request('GET', $node['uri'], ['auth' => ['admin', 'mypass@jir5']]);

      $news = Json::decode($newsData->getBody());

      $savedFile = null;
      if (!empty($news['field_news_photo'])) {
        $localUri = $news['field_news_photo']['und'][0]['uri'];
        $data = file_get_contents(str_replace(' ', '%20', 'https://www.jobinrwanda.com/sites/default/files/news_images' . substr($localUri, strrpos($localUri, '/'))));
        $file = file_save_data($data, 'public://news_images/' .
          $news['field_news_photo']['und'][0]['filename'], FileSystemInterface::EXISTS_REPLACE);
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

      try {
        Node::create([
          'type' => 'news',
          'title' => $news['title'],
          'status' => 1,
          'field_news_body' => ['value' => $news['body']['und'][0]['safe_value'], 'format' => 'full_html'],
          'field_news_resource_link' => empty($news['field_resource_link']) ? [] : $news['field_resource_link']['und'][0]['url'],
          'field_news_photo' => $savedFile instanceof FileInterface ? $savedFile : []
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
