<?php


namespace Drupal\jix_notifier\Plugin\RulesAction;


use DateInterval;
use Drupal;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\jix_settings\Form\SmsServiceConfigurationForm;
use Drupal\node\NodeInterface;
use Drupal\rules\Core\Annotation\RulesAction;
use Drupal\rules\Core\RulesActionBase;
use phpseclib3\Net\SFTP;
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
  private $smsFilesState;

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
    $this->smsFilesState = 'sms.files.' . (new DrupalDateTime())->format('dmY');
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): CreateAndPushSMSFilesAction
  {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('entity_type.manager'));
  }

  protected function doExecute()
  {
    Drupal::logger($this->channel)->info("SMS files creation action triggered.");
    if ($this->haveToBeCreated()) {
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
        } else {
          Drupal::logger($this->channel)->info("No jobs to be sent in SMS available.");
        }
      } catch (InvalidPluginDefinitionException $e) {
        Drupal::logger($this->channel)->error("Plugin definition exception: " . $e->getMessage());
      } catch (PluginNotFoundException $e) {
        Drupal::logger($this->channel)->error("Plugin not found exception: " . $e->getMessage());
      }
    } else {
      Drupal::logger($this->channel)->info("SMS files creation already completed for today.");
    }
  }

  private function createSMSFiles($jobs)
  {
    Drupal::logger($this->channel)->info(count($jobs) . ' jobs found.');
    foreach ($jobs as $jobId => $job) {
      if ($job instanceof NodeInterface) {
        $smsFilesCreatedToday = Drupal::state()->get($this->smsFilesState);
        if ($smsFilesCreatedToday) {
          $nbrOfFiles = intval($smsFilesCreatedToday);
          $result = $this->computeAndPushFiles($job, $nbrOfFiles);
        } else {
          $result = $this->computeAndPushFiles($job);
        }
        if ($result) {
          $job->set('field_job_sent_in_sms', 1);
          try {
            $storage = $this->entityTypeManager->getStorage('node');
            $storage->save($job);
          } catch (EntityStorageException $e) {
            Drupal::logger($this->channel)->error("Entity storage exception: " . $e->getMessage());
          } catch (InvalidPluginDefinitionException $e) {
            Drupal::logger($this->channel)->error("Plugin definition exception: " . $e->getMessage());
          } catch (PluginNotFoundException $e) {
            Drupal::logger($this->channel)->error("Plugin not found exception: " . $e->getMessage());
          }
        }
      }
    }
  }

  private function computeAndPushFiles($job, $nbr = 0): bool
  {
    $maxDailyFiles = intval(Drupal::config(SmsServiceConfigurationForm::SETTINGS)->get('number_daily_jobs'));
    $deadline = $job->get('field_job_application_deadline')->date->format('Ymd');
    $publishedOn = Drupal::service('date.formatter')->format($job->getCreatedTime(), 'custom', 'Ymd');
    $filename = Drupal::config(SmsServiceConfigurationForm::SETTINGS)->get('filename_format');
    $filename = str_replace('[deadline]', $deadline, $filename);
    $filename = str_replace('[publishedOn]', $publishedOn, $filename);
    $filename = str_replace('[jobIdentifier]', $job->id(), $filename);

    if ($nbr < $maxDailyFiles) {
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
        $dir_ready_files_folder = 'public://sms_files/ready/';
        $filePath = $dir_ready_files_folder . $filename;
        $file = file_save_data($smsText, $filePath, FileSystemInterface::EXISTS_REPLACE);
        if ($file !== false) {
          $protocol = Drupal::config(SmsServiceConfigurationForm::SETTINGS)->get('ftp_protocol');
          $host = Drupal::config(SmsServiceConfigurationForm::SETTINGS)->get('ftp_host');
          $port = Drupal::config(SmsServiceConfigurationForm::SETTINGS)->get('ftp_port');
          $directory = Drupal::config(SmsServiceConfigurationForm::SETTINGS)->get('ftp_directory');
          $username = Drupal::config(SmsServiceConfigurationForm::SETTINGS)->get('ftp_username');
          $password = Drupal::config(SmsServiceConfigurationForm::SETTINGS)->get('ftp_password');
          switch ($protocol) {
            case 'ftp':
              $connection = ftp_connect($host, $port, 30);
              if ($connection !== false) {
                if (ftp_login($connection, $username, $password)) {
                  if (ftp_chdir($connection, $directory)) {
                    ftp_pasv($connection, true);
                    $result = ftp_nb_put($connection, $filename, $filePath, FTP_BINARY);
                    switch ($result) {
                      case FTP_FINISHED:
                        Drupal::logger($this->channel)->info($this->t('SMS File @filename created successfully.', ['@filename' => $filename]));
                        $nbr += 1;
                        Drupal::state()->set($this->smsFilesState, $nbr);
                        return true;
                      case FTP_MOREDATA:
                        Drupal::logger($this->channel)->warning($this->t('SMS File @filename creation has more data to process.', ['@filename' => $filename]));
                        break;
                      case FTP_FAILED:
                        Drupal::logger($this->channel)->error($this->t('SMS File @filename creation failed.', ['@filename' => $filename]));
                        break;
                    }
                  } else {
                    Drupal::logger($this->channel)->error($this->t('Folder @folder does not exist', ['@folder' => $directory]));
                  }
                } else {
                  Drupal::logger($this->channel)->error($this->t('Login to FTP server @server failed', ['@server' => $host]));
                }
              }
              break;
            case 'sftp':
              $sftp = new SFTP($host, $port);
              $loggedIn = $sftp->login($username, $password);
              if ($loggedIn) {
                if ($sftp->chdir($directory)) {
                  $result = $sftp->put($filename, $filePath, SFTP::SOURCE_LOCAL_FILE);
                  if ($result) {
                    Drupal::logger($this->channel)->info($this->t('SMS File @filename created successfully.', ['@filename' => $filename]));
                    $nbr += 1;
                    Drupal::state()->set($this->smsFilesState, $nbr);
                    return true;
                  } else {
                    Drupal::logger($this->channel)->error($this->t('SMS File @filename creation failed.', ['@filename' => $filename]));
                  }
                } else {
                  Drupal::logger($this->channel)->error($this->t('Folder @folder does not exist', ['@folder' => $directory]));
                }
              } else {
                Drupal::logger($this->channel)->error($this->t('Login to SFTP server @server failed', ['@server' => $host]));
              }
              break;
          }
        }
      } else {
        Drupal::logger($this->channel)->warning("SMS text is too long");
      }
    }
    return false;
  }

  private function haveToBeCreated(): bool
  {
    $smsFilesCreatedToday = Drupal::state()->get($this->smsFilesState);
    if ($smsFilesCreatedToday) {
      Drupal::logger($this->channel)->info('SMS files created today: ' . $smsFilesCreatedToday);
      $maxDailyFiles = intval(Drupal::config(SmsServiceConfigurationForm::SETTINGS)->get('number_daily_jobs'));
      return intval($smsFilesCreatedToday) < $maxDailyFiles;
    }
    return true;
  }
}
