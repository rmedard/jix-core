<?php


namespace Drupal\jix_notifier\Plugin\RulesAction;


use Drupal;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\rules\Core\Annotation\RulesAction;
use Drupal\rules\Core\RulesActionBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Drupal\webform\Entity\WebformSubmission;
use Psr\Http\Message\ResponseInterface;

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
      $config = Drupal::config('jix_notifier.general.settings');
      $cvSearchUrl = $config->get('cv_search_url');
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
    $job = null;
    $jobId = intval($jobApplication->getElementData('job_application_job'));
    $jobCategory = '';
    if ($jobId > 0) {
      $job = Node::load($jobId);
      $categories = (array)$job->get('field_job_category')->referencedEntities();
      foreach ($categories as $index => $category) {
        if ($category instanceof TermInterface) {
          $jobCategory .= $category->getName();
          if ($index < count($categories) - 1) {
            $jobCategory .= ', ';
          }
        }
      }
    }

    $studyId = $jobApplication->getElementData('job_application_field_study');
    $study = Term::load(intval($studyId));

    $diplomaId = $jobApplication->getElementData('job_application_highest_degree');
    $diploma = Term::load(intval($diplomaId));

    $experienceId = $jobApplication->getElementData('job_application_experience');
    $experience = Term::load(intval($experienceId));

    $cvFileId = $jobApplication->getElementData('job_application_cv_resume_file');
    $cvFile = File::load($cvFileId);

    $candidateProfile = [
      'DateReceived' => date('Y-m-d H:i:s', $jobApplication->getCompletedTime()),
      'FirstName' => $jobApplication->getElementData('job_application_firstname'),
      'LastName' => $jobApplication->getElementData('job_application_lastname'),
      'Email' => $jobApplication->getElementData('job_application_email'),
      'Tel' => $jobApplication->getElementData('job_application_telephone'),
      'JobId' => is_null($job) ? '0' : strval($job->id()),
      'JobUUID' => is_null($job) ? '0' : $job->uuid(),
      'JobTitle' => is_null($job) ? '' : $job->getTitle(),
      'JobCategory' => is_null($job) ? '' : $jobCategory,
      'CoverNote' => $jobApplication->getElementData('job_application_cover_letter'),
      'Nationality' => $jobApplication->getElementData('job_application_nationality'),
      'Diploma' => is_null($diploma) ? '' : $diploma->label(),
      'Study' => is_null($study) ? '' : $study->label(),
      'Languages' => Json::encode($jobApplication->getElementData('job_application_spoken_languages')),
      'Experience' => is_null($experience) ? '' : $experience->label(),
      'Sex' => $jobApplication->getElementData('job_application_sex'),
      'FileName' => $this->cleanupFileUrl(file_create_url($cvFile->getFileUri()), $jobApplication->id()),
      'FileHash' => ''
    ];
    $response = Drupal::httpClient()->post($cvSearchUrl, ['json' => $candidateProfile]);
    if ($response instanceof ResponseInterface) {
      Drupal::logger($this->channel)->info('Response code: ' . $response->getStatusCode()
        . ' | Phrase: ' . $response->getBody()->getContents());
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
