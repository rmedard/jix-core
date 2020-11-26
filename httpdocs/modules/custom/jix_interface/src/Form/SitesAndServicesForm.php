<?php


namespace Drupal\jix_interface\Form;


use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SitesAndServicesForm extends ConfigFormBase
{

  const SETTINGS = 'jix_interface.sites_and_services';

  protected function getEditableConfigNames()
  {
    return [static::SETTINGS];
  }

  public function getFormId()
  {
    return 'jix_interface_sites_services';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config(static::SETTINGS);
    $form['our_services'] = [
      '#type' => 'textarea',
      '#title' => t('Our services - Minimal format for footer'),
      '#default_value' => $config->get('our_services'),
      '#placeholder' =>t('Service 1 etc...|Service 2 etc...'),
      '#description' =>t('Multiple services separated by a vertical bar.')
    ];
    $form['our_sites'] = [
      '#type' => 'textarea',
      '#title' => t('Other other job board websites - Minimal format for footer'),
      '#default_value' => $config->get('our_sites'),
      '#placeholder' =>t('Jobinrwanda.com: www.jobinrwanda.com|Jobinburundi.com: www.jobinburundi.com ...'),
      '#description' =>t('Multiple websites separated by a vertical bar.')
    ];
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('our_services', $form_state->getValue('our_services'))
      ->set('our_sites', $form_state->getValue('our_sites'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
