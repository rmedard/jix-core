<?php


namespace Drupal\jix_notifier\Plugin\RulesAction;


use Drupal;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityInterface;
use Drupal\jix_notifier\Form\NotifierGeneralSettingsForm;
use Drupal\rules\Core\Annotation\RulesAction;
use Drupal\rules\Core\RulesActionBase;
use Drupal\webform\Entity\WebformSubmission;
use JetBrains\PhpStorm\Pure;

/**
 * Class OnJobApplicationAction
 * @package Drupal\jix_notifier\Plugin\RulesAction
 *
 * @RulesAction(
 *     id = "rules_action_send_job_application_cv_cearch",
 *     label = @Translation("Send Job Application to CV Search Action"),
 *     category = @Translation("Jix Custom Actions"),
 *     context_definitions = {
 *       "entity" = @ContextDefinition("entity:webform_submission", label = @Translation("Submission object"), description = @Translation("Submitted data"))
 *     }
 * )
 */
class SendJobApplicationToCVSearchAction extends RulesActionBase
{

  private string $channel;

  #[Pure]
  public function __construct(array $configuration, $plugin_id, $plugin_definition)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->channel = 'jix_notifier';
  }

  /**
   * @param EntityInterface $entity
   */
  protected function doExecute(EntityInterface $entity)
  {
    if ($entity instanceof WebformSubmission) {
      $cvSearchUrl = trim(Drupal::config(NotifierGeneralSettingsForm::SETTINGS)->get('cv_search_url'));
      if (!empty($cvSearchUrl)) {
        if (UrlHelper::isValid($cvSearchUrl)) {
          $applicationsService = Drupal::service('jix_notifier.job_applications_service');
          $applicationsService->sendToCvSearch($entity, $cvSearchUrl);
        } else {
          Drupal::logger($this->channel)->error('Invalid CV Search Url: ' . $cvSearchUrl);
        }
      }
    }
  }
}
