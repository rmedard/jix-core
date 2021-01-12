<?php


namespace Drupal\jix_notifier\Plugin\RulesAction;


use Drupal;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Drupal\rules\Core\Annotation\RulesAction;
use Drupal\rules\Core\RulesActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class UnpublishJobsOnEmployerUnpublished
 * @package Drupal\jix_notifier\Plugin\RulesAction
 *
 * @RulesAction(
 *   id = "rules_action_unpublish_jobs_employer_unpub",
 *   label = @Translation("Unpublish Jobs On Employer Unpublished"),
 *   category = @Translation("Jix Custom Actions"),
 *   context_definitions = {
 *      "entity" = @ContextDefinition("entity:node", label = @Translation("Employer object"), description = @Translation("Employer data"), required = true)
 *   }
 * )
 */
class UnpublishJobsOnEmployerUnpublished extends RulesActionBase implements ContainerFactoryPluginInterface
{
  private $channel;

  /**
   * The entity type manager service.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * UnpublishJobsOnEmployerUnpublished constructor.
   * @param array $configuration
   * @param string $plugin_id
   * @param $plugin_definition
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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): UnpublishJobsOnEmployerUnpublished
  {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('entity_type.manager'));
  }

  protected function doExecute(NodeInterface $entity)
  {
    try {
      $storage = $this->entityTypeManager->getStorage('node');
      $query = $storage->getQuery()->condition('type', 'job')
        ->condition('status', NodeInterface::PUBLISHED)
        ->condition('field_job_employer.target_id', $entity->id());
      $jobIds = $query->execute();
      if (isset($jobIds) and count($jobIds) > 0) {
        $jobs = $storage->loadMultiple($jobIds);
        foreach ($jobs as $jobId => $job) {
          if ($job instanceof NodeInterface) {
            $job->setUnpublished();
            $job->save();
            Drupal::logger($this->channel)->info(t('Job @id unpublished following disabled employer @empId', ['@id' => $jobId, '@empId' => $entity->id()]));
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
