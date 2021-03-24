<?php


namespace Drupal\jix_settings\Form;


use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class EmailsServiceConfigurationForm extends ConfigFormBase
{

  const SETTINGS = 'jix_settings.email.settings';

  protected function getEditableConfigNames(): array
  {
    return [static::SETTINGS];
  }

  public function getFormId(): string
  {
    return 'jix_emails_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $config = $this->config(static::SETTINGS);
    $form['our_services'] = [
      '#type' => 'textarea',
      '#title' => t('Our services - Minimal format for footer'),
      '#default_value' => $config->get('our_services'),
      '#placeholder' =>t('Service 1 etc...|Service 2 etc...'),
      '#description' =>t('Multiple services separated by a vertical bar.')
    ];
    return parent::buildForm($form, $form_state);
  }
}
