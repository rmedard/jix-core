<?php


namespace Drupal\jix_notifier\Plugin\RulesAction;


use Drupal;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\node\NodeInterface;
use Drupal\rules\Core\Annotation\RulesAction;
use Drupal\rules\Core\RulesActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class UnsubscribeUserAction
 * @package Drupal\jix_notifier\Plugin\RulesAction
 *
 * @RulesAction(
 *     id = "rules_action_unpublish_expired_jobs",
 *     label = @Translation("Unpublish Expired Jobs Action"),
 *     category = @Translation("Jix Custom Actions")
 * )
 */
class UnpublishExpiredJobsAction extends RulesActionBase implements ContainerFactoryPluginInterface
{

  private $channel;

  /**
   * The entity type manager service.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Overrides \Drupal\Component\Plugin\PluginBase::__construct().
   *
   * Overrides the construction of context aware plugins to allow for
   * unvalidated constructor based injection of contexts.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->channel = 'jix_notifier';
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('entity_type.manager'));
  }

  protected function doExecute()
  {
    try {
      $storage = $this->entityTypeManager->getStorage('node');
      $now = new DrupalDateTime('now');
      $query = $storage->getQuery()->condition('type', 'job')
        ->condition('status', NodeInterface::PUBLISHED)
        ->condition('field_job_application_deadline', $now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT), '<');
      $expiredJobIds = $query->execute();
      if (isset($expiredJobIds) and count($expiredJobIds) > 0) {
        $expiredJobs = $storage->loadMultiple($expiredJobIds);
        foreach ($expiredJobs as $jobId => $expiredJob) {
          if ($expiredJob instanceof NodeInterface) {
            $expiredJob->setUnpublished();
            $expiredJob->save();
            Drupal::logger($this->channel)->info(t('Expired job @id unpublished', ['@id' => $jobId]));
          }
        }
      }
    } catch (InvalidPluginDefinitionException $e) {
      Drupal::logger($this->channel)->error("Plugin definition exception: " . $e->getMessage());
    } catch (PluginNotFoundException $e) {
      Drupal::logger($this->channel)->error("Plugin not found exception: " . $e->getMessage());
    } catch (EntityStorageException $e) {
      Drupal::logger($this->channel)->error("Unpublishing node failed: " . $e->getMessage());
    }
  }
}
