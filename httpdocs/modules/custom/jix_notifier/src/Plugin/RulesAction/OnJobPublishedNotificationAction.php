<?php


namespace Drupal\jix_notifier\Plugin\RulesAction;


use Drupal;
use Drupal\jix_notifier\Utils\EmailData;
use Drupal\jix_notifier\Utils\NotificationType;
use Drupal\node\NodeInterface;
use Drupal\rules\Core\Annotation\RulesAction;
use Drupal\rules\Core\RulesActionBase;

/**
 * Class OnJobPublishedNotificationAction
 * @package Drupal\jix_notifier\Plugin\RulesAction
 *
 * @RulesAction(
 *   id = "rules_action_on_job_published_action",
 *     label = @Translation("On Job Published Action"),
 *     category = @Translation("Jix Custom Actions"),
 *     context_definitions = {
 *       "entity" = @ContextDefinition("entity:node", label = @Translation("Job object"), description = @Translation("Job data"), required = true)
 *     }
 * )
 */
class OnJobPublishedNotificationAction extends RulesActionBase
{

  private string $channel;

  public function __construct(array $configuration, $plugin_id, $plugin_definition)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->channel = 'jix_notifier';
  }

  protected function doExecute(NodeInterface $entity) {
    $emailService = Drupal::service('jix_notifier.email_service');
    $emailService->send(new EmailData(NotificationType::NEW_JOB_PUBLISHED, $entity));
  }
}
