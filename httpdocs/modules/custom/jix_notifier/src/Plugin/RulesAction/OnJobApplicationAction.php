<?php


namespace Drupal\jix_notifier\Plugin\RulesAction;


use Drupal;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\rules\Core\Annotation\RulesAction;
use Drupal\rules\Core\RulesActionBase;
use Drupal\webform\Entity\WebformSubmission;
use GuzzleHttp\Exception\ClientException;
use JetBrains\PhpStorm\Pure;

/**
 * Class OnJobApplicationAction
 * @package Drupal\jix_notifier\Plugin\RulesAction
 *
 * @RulesAction(
 *     id = "rules_action_new_job_application",
 *     label = @Translation("New Job Application Action"),
 *     category = @Translation("Jix Custom Actions"),
 *     context_definitions = {
 *       "entity" = @ContextDefinition("entity:webform_submission", label = @Translation("Submission object"), description = @Translation("Submitted data"))
 *     }
 * )
 */
class OnJobApplicationAction extends RulesActionBase
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
      $cvSearchUrl = Drupal::config('jix_notifier.general.settings')->get('cv_search_url');
      if (isset($cvSearchUrl) && $cvSearchUrl !== '') {
        $this->sendToCvSearch($entity, $cvSearchUrl);
      }
    }
  }

  /**
   * @param WebformSubmission $jobApplication
   * @param string $cvSearchUrl
   */
  private function sendToCvSearch(WebformSubmission $jobApplication, string $cvSearchUrl)
  {
    $applicationsService = Drupal::service('jix_notifier.job_applications_service');
    $data = $applicationsService->getCvSearchJsonData($jobApplication);
    try {
      $response = Drupal::httpClient()->post($cvSearchUrl, ['json' => $data]);
      if ($response->getStatusCode() == 200) {
        try {
          $jobApplication->setElementData('field_application_sync', 'Yes');
          $jobApplication->save();
          Drupal::logger($this->channel)->info('Job application {'. $jobApplication->id() .'} sent to CV Search');
        } catch (EntityStorageException $e) {
          Drupal::logger($this->channel)->error('Saving application failed: ' . $e->getMessage());
        }
      } else {
        Drupal::logger($this->channel)->error(t('Synchronizing application @id failed with error code @code: @message',
          [
            '@id' => $jobApplication->id(),
            '@code' => $response->getStatusCode(),
            '@message' => $response->getReasonPhrase()
          ]));
      }
    } catch (ClientException $exception) {
      Drupal::logger($this->channel)->error('Cv Search Client Exception: ' . $exception->getMessage());
    }
  }

  /** Remove this method when https://www.drupal.org/project/webform/issues/3175525 is resolved
   * @param $url
   * @param $submissionId
   * @return string
   */
  private function cleanupFileUrl($url, $submissionId): string {
    if (str_contains($url, '_sid_')) {
      return str_replace('_sid_', strval($submissionId), $url);
    }
    return $url;
  }
}
