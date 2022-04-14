<?php


namespace Drupal\jix_notifier\Form;


use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class NotifierGeneralSettingsForm extends ConfigFormBase
{

  const SETTINGS = 'jix_notifier.general.settings';

  protected function getEditableConfigNames(): array
  {
    return [static::SETTINGS];
  }

  public function getFormId(): string
  {
    return 'jix_notifier_general_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $config = $this->config(static::SETTINGS);
    $form['general_newsletter_url'] = [
      '#type' => 'textfield',
      '#title' => t('General newsletter subscription url'),
      '#default_value' => $config->get('general_newsletter_url'),
      '#description' => t('Enter a valid and absolute URL. No query parameters.')
    ];
    $form['general_newsletter_id'] = [
      '#type' => 'textfield',
      '#title' => t('Newsletter Id'),
      '#default_value' => empty($config->get('general_newsletter_id')) ? "13" : $config->get('general_newsletter_id'),
      '#description' => t('Enter a valid news letter id')
    ];
    $form['cv_search_url'] = [
      '#type' => 'textfield',
      '#title' => t('CV search url'),
      '#default_value' => $config->get('cv_search_url'),
      '#description' => t('Enter a valid and absolute URL. No query parameters.')
    ];
    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    $newsletterUrl = $form_state->getValue('general_newsletter_url');
    if (isset($newsletterUrl)) {
      if (!UrlHelper::isValid($newsletterUrl, true)) {
        $form_state->setErrorByName('general_newsletter_url', 'Invalid URL. This url has to be valid and absolute.');
      } elseif (str_contains($newsletterUrl, '?')) {
        $form_state->setErrorByName('general_newsletter_url', 'Invalid URL. No query parameters required.');
      }
    }
    if (!$form_state->isValueEmpty('cv_search_url')) {
      $cvSearchUrl = $form_state->getValue('cv_search_url');
      if (!UrlHelper::isValid($cvSearchUrl, true)) {
        $form_state->setErrorByName('cv_search_url', 'Invalid URL. This url has to be valid and absolute.');
      } elseif (str_contains($cvSearchUrl, '?')) {
        $form_state->setErrorByName('cv_search_url', 'Invalid URL. No query parameters required.');
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $id = trim($form_state->getValue('general_newsletter_id'));
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('general_newsletter_url', $form_state->getValue('general_newsletter_url'))
      ->set('general_newsletter_id', empty($id) ? "13" : $id)
      ->set('cv_search_url', $form_state->getValue('cv_search_url'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
