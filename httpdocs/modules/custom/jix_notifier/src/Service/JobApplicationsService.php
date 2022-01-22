<?php

namespace Drupal\jix_notifier\Service;

use Drupal;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;

class JobApplicationsService
{

  private string $channel;

  public function __construct()
  {
    $this->channel = 'jix_notifier';
  }

  public function sendToCvSearch(WebformSubmission $jobApplication, string $cvSearchUrl)
  {
    $data = $this->getCvSearchJsonData($jobApplication);
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
    } catch (RequestException | ServerException $exception) {
      Drupal::logger($this->channel)->error('Cv Search Request Exception: ' . $exception->getMessage() . ' | Request body: ' . Json::encode($data));
    }
  }

  private function getCvSearchJsonData(WebformSubmissionInterface $submission): array
  {
    $jobId = $submission->getElementData('job_application_job');
    $job = null;
    $jobCategory = '';
    if (is_numeric($jobId)) {
      $job = Node::load($jobId);
      if (!is_null($job)) {
        $jobCategoryField = $job->get('field_job_category');
        if ($jobCategoryField instanceof EntityReferenceFieldItemListInterface) {
          $categories = $jobCategoryField->referencedEntities();
          foreach ($categories as $category) {
            if ($category instanceof TermInterface) {
              $jobCategory .= $category->getName() . ', ';
            }
          }
        }
      }
    }

    $languages = '';
    $languageField = $submission->getElementData('job_application_spoken_languages');
    if (is_array($languageField)) {
      foreach ($languageField as $key => $language) {
        $languages .= ucfirst($key) . ':' . ucfirst($language) . ', ';
      }
    }

    $cvFileId = intval($submission->getElementData('job_application_cv_resume_file'));
    $experienceId = intval($submission->getElementData('job_application_experience'));
    $diplomaId = intval($submission->getElementData('job_application_highest_degree'));
    $studyId = intval($submission->getElementData('job_application_field_study'));

    $cvFileUrl = '';
    if ($cvFileId > 0) {
      $fileUrlGenerator = Drupal::service('file_url_generator');
      if ($fileUrlGenerator instanceof FileUrlGeneratorInterface) {
        $cvFile = File::load($cvFileId);
        if (!is_null($cvFile)) {
          $cvFileUrl = $fileUrlGenerator->generateAbsoluteString($cvFile->getFileUri());
        }
      }
    }

    $coverLetter = $submission->getElementData('job_application_cover');
    $coverLetter = empty($coverLetter) ? '' : strip_tags($coverLetter['value']);

    return [
      'DateReceived' => date('Y-m-d H:m:s', $submission->getCompletedTime()),
      'FirstName' => $submission->getElementData('job_application_firstname'),
      'LastName' => $submission->getElementData('job_application_lastname'),
      'Email' => $submission->getElementData('job_application_email'),
      'Tel' => $submission->getElementData('job_application_telephone'),
      'JobId' => $jobId,
      'JobTitle' => is_null($job) ? '' : $job->getTitle(),
      'JobCategory' => substr_replace(trim($jobCategory), '', -1),
      'CoverNote' => $coverLetter,
      'Nationality' => $submission->getElementData('job_application_nationality'),
      'Diploma' => $diplomaId > 0 ? Term::load($diplomaId)->getName() : '',
      'Study' => $studyId > 0 ? Term::load($studyId)->getName() : '',
      'Languages' => substr_replace(trim($languages), '', -1),
      'Experience' => $experienceId > 0 ? Term::load($experienceId)->getName() : '',
      'Sex' => $submission->getElementData('job_application_sex'),
      'cvUrl' => $cvFileUrl
    ];
  }

}
