<?php


namespace Drupal\jix_notifier\Plugin\WebformHandler;


use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\webform\Annotation\WebformHandler;
use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Class OnJobApplicationSaved
 * @package Drupal\jix_notifier\Plugin\WebformHandler
 *
 * @WebformHandler(
 *   id="On Job Application Saved",
 *   label= @Translation("On Job Application Saved"),
 *   category= @Translation("Job Application Creation"),
 *   description= @Translation("Sends email after job application is submitted"),
 *   cardinality= \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class OnJobApplicationSaved extends EmailWebformHandler
{

  /**
   * @param WebformSubmissionInterface $webform_submission
   * @param array $message
   * @return mixed|void
   */
  public function sendMessage(WebformSubmissionInterface $webform_submission, array $message)
  {
    $values = $webform_submission->getData();
    $jobId = $values['job_application_job'];
    if (isset($jobId)) {
      $job = Node::load($jobId);
      if ($job instanceof NodeInterface) {
        $recipients = $job->get('field_job_application_email')->value;
        if (isset($job->get('field_job_additional_email')->value) and !empty($job->get('field_job_additional_email')->value)) {
          $recipients .= ',' . $job->get('field_job_additional_email')->value;
        }
        $message['to_mail'] = $recipients;
        $message['reply_to'] = $values['job_application_email'];
        $message['subject'] = $this->t('A new job application for @title (Ref: @jobId/@applicationId)', [
          '@title' => $job->getTitle(),
          '@jobId' => $jobId,
          '@applicationId' => $webform_submission->id()
        ]);
      }
    }
    return parent::sendMessage($webform_submission, $message);
  }
}
