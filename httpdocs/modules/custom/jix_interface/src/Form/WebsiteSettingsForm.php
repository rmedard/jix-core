<?php


namespace Drupal\jix_interface\Form;


use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class WebsiteSettingsForm extends ConfigFormBase
{

  const SETTINGS = 'jix_interface.website_settings';

  protected function getEditableConfigNames()
  {
    return [static::SETTINGS];
  }

  public function getFormId()
  {
    return 'jix_interface_website_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config(static::SETTINGS);
    $form['site_owner'] = [
      '#type' => 'textfield',
      '#title' => t('Site owner'),
      '#default_value' => $config->get('site_owner')
    ];
    $form['site_address_line_1'] = [
      '#type' => 'textfield',
      '#title' => t('Site address line 1'),
      '#default_value' => $config->get('site_address_line_1')
    ];
    $form['site_address_line_2'] = [
      '#type' => 'textfield',
      '#title' => t('Site address line 2'),
      '#default_value' => $config->get('site_address_line_2')
    ];
    $form['site_phone'] = [
      '#type' => 'tel',
      '#title' => t('Site phone'),
      '#default_value' => $config->get('site_phone')
    ];
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('site_owner', $form_state->getValue('site_owner'))
      ->set('site_address_line_1', $form_state->getValue('site_address_line_1'))
      ->set('site_address_line_2', $form_state->getValue('site_address_line_2'))
      ->set('site_phone', $form_state->getValue('site_phone'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
