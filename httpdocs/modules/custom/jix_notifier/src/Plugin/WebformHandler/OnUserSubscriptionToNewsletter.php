<?php

namespace Drupal\jix_notifier\Plugin\WebformHandler;

use Drupal;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\jix_notifier\Form\NotifierGeneralSettingsForm;
use Drupal\webform\Annotation\WebformHandler;
use Drupal\webform\Plugin\WebformHandler\RemotePostWebformHandler;
use Drupal\webform\WebformSubmissionInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;

/**
 * Synchronise User details on registration
 *
 * @WebformHandler(
 *   id = "Send user details to newsletter engine",
 *   label= @Translation("Send user details to newsletter engine"),
 *   category= @Translation("Custom Webform Handlers"),
 *   description= @Translation("Send user details to newsletter engine"),
 *   cardinality= \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class OnUserSubscriptionToNewsletter extends RemotePostWebformHandler
{

  /**
   * @param WebformSubmissionInterface $webform_submission
   * @param bool $update
   * @return void
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE): void
  {
    if ($webform_submission->getState() === WebformSubmissionInterface::STATE_COMPLETED) {
      $config = Drupal::config(NotifierGeneralSettingsForm::SETTINGS);
      $logger = Drupal::logger("jix_notifier");
      $newsletterUrl = trim($config->get('general_newsletter_url'));
      if (!empty($newsletterUrl)) {
        if (UrlHelper::isValid($newsletterUrl)) {
          $validationEntity = $webform_submission->validate();
          if ($validationEntity->count() > 0) {
            $messages = [];
            foreach ($validationEntity->getEntityViolations() as $violation) {
              $messages[] = $violation->getMessage();
            }
            Drupal::logger("jix_notifier")->error('Invalid job submission: | Error Messages: ' . Json::encode($messages));
          } else {
            $data = $webform_submission->getData();
            $body = [
              'name' => $data['gen_news_noms'],
              'email' => $data['gen_news_email'],
              'newsletterId' => trim($config->get('general_newsletter_id'))
            ];
            try {
              $response = Drupal::httpClient()->post($newsletterUrl, $body);
              if ($response->getStatusCode() == 200) {
                $logger->info('User registration {'. $data['gen_news_email'] .'} sent to Newsletter Engine | Body: <pre><code>' . print_r($body, TRUE) . '</code></pre>');
              } else {
                $logger->error(t('Synchronizing user @email failed with error code @code: @message',
                  [
                    '@email' => $data['gen_news_email'],
                    '@code' => $response->getStatusCode(),
                    '@message' => $response->getReasonPhrase()
                  ]));
              }
            } catch (ClientException $exception) {
              $logger->error('Newsletter Engine Client Exception: ' . $exception->getMessage() . ' | Body: <pre><code>' . print_r($body, TRUE) . '</code></pre>');
            } catch (RequestException | ServerException $exception) {
              $logger->error('Newsletter Engine Request Exception: ' . $exception->getMessage() . ' | Request body: ' . Json::encode($body));
            }
          }
        } else {
          $logger->error('Invalid Newsletter Url: ' . $newsletterUrl);
        }
      }
    }
  }
}
