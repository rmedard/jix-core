<?php


namespace Drupal\jix_settings\Form;


use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SocialMediaSettingsForm extends ConfigFormBase
{

  const SETTINGS = 'jix_settings.social_media.settings';

  protected function getEditableConfigNames(): array
  {
    return [static::SETTINGS];
  }

  public function getFormId(): string
  {
    return 'jix_social_media_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $config = $this->config(static::SETTINGS);
    $form['facebook_page'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook page'),
      '#default_value' => $config->get('facebook_page'),
      '#description' => $this->t('The full URL to the target facebook page')
    ];
    $form['twitter_page'] = [
      '#type' => 'url',
      '#title' => $this->t('Twitter page'),
      '#default_value' => $config->get('twitter_page'),
      '#description' => $this->t('The full URL to the target twitter page')
    ];
    $form['instagram_page'] = [
      '#type' => 'url',
      '#title' => $this->t('Instagram page'),
      '#default_value' => $config->get('instagram_page'),
      '#description' => $this->t('The full URL to the target instagram page')
    ];
    $form['youtube_page'] = [
      '#type' => 'url',
      '#title' => $this->t('Youtube page'),
      '#default_value' => $config->get('youtube_page'),
      '#description' => $this->t('The full URL to the target youtube page')
    ];
    $form['linkedin_page'] = [
      '#type' => 'url',
      '#title' => $this->t('Linkedin page'),
      '#default_value' => $config->get('linkedin_page'),
      '#description' => $this->t('The full URL to the target linkedin page')
    ];
    $form['whatsapp_call_link'] = [
      '#type' => 'url',
      '#title' => $this->t('Whatsapp call link'),
      '#default_value' => $config->get('whatsapp_call_link'),
      '#description' => $this->t('The full link for whatsapp call')
    ];
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('facebook_page', $form_state->getValue('facebook_page'))
      ->set('twitter_page', $form_state->getValue('twitter_page'))
      ->set('instagram_page', $form_state->getValue('instagram_page'))
      ->set('youtube_page', $form_state->getValue('youtube_page'))
      ->set('linkedin_page', $form_state->getValue('linkedin_page'))
      ->set('whatsapp_call_link', $form_state->getValue('whatsapp_call_link'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
