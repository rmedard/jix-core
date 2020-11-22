<?php


namespace Drupal\jix_notifier\Form;


use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class NotifierGeneralSettingsForm extends ConfigFormBase
{

  const SETTINGS = 'jix_notifier.general.settings';

  protected function getEditableConfigNames()
  {
    return [static::SETTINGS];
  }

  public function getFormId()
  {
    return 'jix_notifier_general_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config(static::SETTINGS);
    $form['general_newsletter_url'] = [
      '#type' => 'textfield',
      '#title' => t('General newsletter subscription url'),
      '#default_value' => $config->get('general_newsletter_url'),
      '#description' => t('Enter a valid and absolute URL. No query parameters.')
    ];
    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    $url = $form_state->getValue('general_newsletter_url');
    if (isset($url)) {
      if (!UrlHelper::isValid($url, true)) {
        $form_state->setErrorByName('general_newsletter_url', 'Invalid URL. This url has to be valid and absolute.');
      } elseif (strpos($url, '?') !== false) {
        $form_state->setErrorByName('general_newsletter_url', 'Invalid URL. No query parameters required.');
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('general_newsletter_url', $form_state->getValue('general_newsletter_url'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
