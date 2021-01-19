<?php


namespace Drupal\jix_notifier\Plugin\RulesAction;


use DateInterval;
use DateTime;
use Drupal;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\jix_settings\Form\SmsServiceConfigurationForm;
use Drupal\node\NodeInterface;
use Drupal\rules\Core\Annotation\RulesAction;
use Drupal\rules\Core\RulesActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CreateAndPushSMSFilesAction
 * @package Drupal\jix_notifier\Plugin\RulesAction
 *
 * @RulesAction(
 *   id = "rules_action_create_push_sms_files",
 *   label = @Translation("Create and Push SMS Files Action"),
 *   category = @Translation("Jix Custom Actions")
 * )
 */
class CreateAndPushSMSFilesAction extends RulesActionBase implements ContainerFactoryPluginInterface
{

  private $channel;

  /**
   * The entity type manager service.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->channel = 'jix_notifier';
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): CreateAndPushSMSFilesAction
  {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('entity_type.manager'));
  }

  protected function doExecute()
  {
    try {
      $storage = $this->entityTypeManager->getStorage('node');
      $now = new DrupalDateTime('now');
      $fourDaysAhead = $now->add(new DateInterval('P4D'));
      $numberOfDailyFiles = Drupal::config(SmsServiceConfigurationForm::SETTINGS)->get('number_daily_jobs');
      $dateFormat = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
      $query = $storage->getQuery()
        ->condition('type', 'job')
        ->condition('status', NodeInterface::PUBLISHED)
        ->condition('field_job_sent_in_sms', false)
        ->condition('field_job_application_deadline', $fourDaysAhead->format($dateFormat), '>=')
        ->range(0, intval($numberOfDailyFiles))
        ->sort('field_job_posting_plan', 'DESC');
      $jobIds = $query->execute();
      if (isset($jobIds) and count($jobIds) > 0) {
        $jobs = $storage->loadMultiple($jobIds);
        $this->createSMSFiles($jobs);
      }
    } catch (InvalidPluginDefinitionException $e) {
      Drupal::logger($this->channel)->error("Plugin definition exception: " . $e->getMessage());
    } catch (PluginNotFoundException $e) {
      Drupal::logger($this->channel)->error("Plugin not found exception: " . $e->getMessage());
    } catch (EntityStorageException $e) {
      Drupal::logger($this->channel)->error("Fetching jobs for SMS failed: " . $e->getMessage());
    }
  }

  private function createSMSFiles($jobs)
  {
    foreach ($jobs as $jobId => $job) {
      if ($job instanceof NodeInterface) {
        $smsText = $this->t('@title recruits at @city-@country. Apply before @deadline on @website|@deadline',
          [
            '@title' => $job->label(),
            '@city' => $job->get('field_job_city')->value,
            '@country' => $job->get('field_job_country')->value,
            '@deadline' => $job->get('field_job_application_deadline')->date->format('d/m/Y'),
            '@website' => Drupal::request()->getHost()
          ]
        );
        if ($smsText->count() <= 171) {
          $deadline = $job->get('field_job_application_deadline')->date->format('Ymd');
          $publishedOn = Drupal::service('date.formatter')->format($job->getCreatedTime(), 'custom', 'Ymd');
          $filename = Drupal::config(SmsServiceConfigurationForm::SETTINGS)->get('filename_format');
          $filename = str_replace('[deadline]', $deadline, $filename);
          $filename = str_replace('[publishedOn]', $publishedOn, $filename);
          $filename = str_replace('[jobIdentifier]', $job->id(), $filename);
          Drupal::logger($this->channel)->info($smsText . ' | ' . $filename);
        }
      }
    }
  }
}
