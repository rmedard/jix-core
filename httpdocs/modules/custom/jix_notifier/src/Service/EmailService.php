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
  public function send(EmailData $emailData): void
  {
    if ($this->mailManager instanceof MailManagerInterface) {
      $to = '';
      $params = [];
      $replyTo = '';
      $langCode = Drupal::languageManager()->getCurrentLanguage()->getId();
      $systemEmail = Drupal::config('system.site')->get('mail');
      switch ($emailData->getNotificationType()) {
        case NotificationType::NEW_JOB_SAVED:
          $job = $emailData->getEntity();
          $to = $systemEmail;
          $replyTo = $systemEmail;
          $params['cc'] = $job->get('field_job_contact_email')->value;
          $params['bcc'] = $systemEmail;
          $params['subject'] = t('A new job has been submitted', [], ['langcode' => $langCode]);
          break;
        case NotificationType::NEW_JOB_PUBLISHED:
          $job = $emailData->getEntity();
          $to = $job->get('field_job_contact_email')->value;
          $params['subject'] = t('Your job has been validated and published.', [], ['langcode' => $langCode]);
          $params['bcc'] = $systemEmail;
          $replyTo = $systemEmail;
          break;
        case NotificationType::CREDIT_THRESHOLD_REACHED:
          $employer = $emailData->getEntity();
          $to = $systemEmail;
          $replyTo = $systemEmail;
          $params['subject'] = t('Notification: Employer credit threshold reached', [], ['langcode' => $langCode]);
          break;
      }

      $params['message'] = Markup::create($this->getEmailHtmlContent($emailData->getNotificationType(), $emailData->getEntity()));
      $result = $this->mailManager->mail($this->channel, $emailData->getNotificationType(),
        $to, $langCode, $params, $replyTo, TRUE);
      if (intval($result['result']) != 1) {
        $message = t('There was a problem sending notification email. Type: <b>@type</b>', ['@type' => $emailData->getNotificationType()]);
        Drupal::logger($this->channel)
          ->error($message . ' Whole Error: ' . Json::encode($result));
      } else {
        $message = t('An email notification of type <b>@type</b> has been sent successfully', ['@type' => $emailData->getNotificationType()]);
        Drupal::logger($this->channel)->notice($message);
      }
    }
  }

  private function getEmailHtmlContent($notificationType, $emailPayload): string
  {
    $templatePath = '';
    $variables = [];
    $modulePath = Drupal::moduleHandler()->getModule($this->channel)->getPath();
    $host = Drupal::request()->getSchemeAndHttpHost();
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
      case NotificationType::CREDIT_THRESHOLD_REACHED:
        $variables = [
          'employer' => $emailPayload,
        ];
        $templatePath = '/templates/jix-notifier-employer-credit-threshold-reached.html.twig';
        break;
    }
    $variables['assets'] = [
      'youtube_logo' => $host . '/' . $modulePath . '/templates/icons/youtube_logo.png',
      'facebook_logo' => $host . '/' . $modulePath . '/templates/icons/facebook_logo.png',
      'instagram_logo' => $host . '/' . $modulePath . '/templates/icons/instagram_logo.png',
      'twitter_logo' => $host . '/' . $modulePath . '/templates/icons/twitter_logo.png',
      'linkedin_logo' => $host . '/' . $modulePath . '/templates/icons/linkedin_logo.png',
      'whatsapp_logo' => $host . '/' . $modulePath . '/templates/icons/whatsapp_logo.png',
      'jix_logo' => $host . '/' . $modulePath . '/templates/icons/hrms_logo.png',
      'hir_logo' => $host . '/' . $modulePath . '/templates/icons/hir_logo.png',
      'sic_logo' => $host . '/' . $modulePath . '/templates/icons/sic_logo.png',
    ];
    Drupal::logger('email_service')->info('Sending email of type: ' . $notificationType);
    return $this->twigService->loadTemplate($modulePath . $templatePath)->render($variables);
  }
}
