<?php

namespace Drupal\jix_notifier\Plugin\RulesAction;

use Drupal\rules\Core\Annotation\RulesAction;
use Drupal\rules\Core\RulesActionBase;

/**
 * Class SyncWithCvSearchAction
 * @package Drupal\jix_notifier\Plugin\RulesAction
 *
 * @RulesAction(
 *     id = "rules_action_sync_cv_search",
 *     label = @Translation("Sync With CV Search Action"),
 *     category = @Translation("Jix Custom Actions"),
 *     context_definitions = {
 *       "entity" = @ContextDefinition("entity:webform_submission", label = @Translation("Submission object"), description = @Translation("Submitted data"))
 *     }
 * )
 */
class SyncWithCvSearchAction extends RulesActionBase
{
  private string $channel;



}
