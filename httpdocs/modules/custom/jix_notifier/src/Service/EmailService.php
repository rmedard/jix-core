<?php


namespace Drupal\jix_notifier\Service;


use Drupal;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\jix_notifier\Utils\EmailData;
use Drupal\jix_notifier\Utils\NotificationType;

class EmailService
{

  private string $channel;
  private mixed $mailManager;
  private mixed $twigService;

  /**
   * EmailService constructor.
   */
  public function __construct()
  {
    $this->channel = 'jix_notifier';
    $this->mailManager = Drupal::service('plugin.manager.mail');
    $this->twigService = Drupal::service('twig');
  }


  /**
   * @param EmailData $emailData
   */
  public function send(EmailData $emailData)
  {
    if ($this->mailManager instanceof MailManagerInterface) {
      $to = '';
      $params = [];
      $replyTo = '';
      $langCode = Drupal::languageManager()->getCurrentLanguage()->getId();
      switch ($emailData->getNotificationType()) {
        case NotificationType::NEW_JOB_SAVED:
          $job = $emailData->getEntity();
          $to = Drupal::config('system.site')->get('mail');
          $replyTo = Drupal::config('system.site')->get('mail');
          $params['cc'] = $job->get('field_job_contact_email')->value;
          $params['subject'] = t('A new job has been submitted', [], ['langcode' => $langCode]);
          break;
        case NotificationType::NEW_JOB_PUBLISHED:
          $job = $emailData->getEntity();
          $to = $job->get('field_job_contact_email')->value;
          $params['subject'] = t('Your job has been validated and published.', [], ['langcode' => $langCode]);
          $replyTo = Drupal::config('system.site')->get('mail');
          break;
      }

      $params['message'] = Markup::create($this->getEmailHtmlContent($emailData->getNotificationType(), $emailData->getEntity()));
      $result = $this->mailManager->mail($this->channel, $emailData->getNotificationType(),
        $to, $langCode, $params, $replyTo, TRUE);
      if (intval($result['result']) != 1) {
        $message = t('There was a problem sending notification email');
        Drupal::logger($this->channel)
          ->error($message . ' Whole Error: ' . Json::encode($result));
      } else {
        $message = t('An email notification has been sent successfully');
        Drupal::logger($this->channel)->notice($message);
      }
    }
  }

  private function getEmailHtmlContent($notificationType, $emailPayload): string
  {
    $templatePath = '';
    $variables = [];
    switch ($notificationType) {
      case NotificationType::NEW_JOB_SAVED:
        $variables = [
          'job' => $emailPayload,
          'recipient' => $emailPayload->get('field_job_contact_name')->value
        ];
        $templatePath = '/templates/jix-notifier-new-job-saved.html.twig';
        break;
      case NotificationType::NEW_JOB_PUBLISHED:
        $variables = [
          'job' => $emailPayload,
          'recipient' => $emailPayload->get('field_job_contact_name')->value
        ];
        $templatePath = '/templates/jix-notifier-new-job-published.html.twig';
        break;
    }
    $modulePath = Drupal::service('extension.list.module')->getPath($this->channel);
    return $this->twigService->loadTemplate($modulePath . $templatePath)->render($variables);
  }
}
