<?php


namespace Drupal\jix_interface\Form;


use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class PostingPlansPageSettingsForm extends ConfigFormBase
{

  const SETTINGS = 'jix_interface.posting_plans_page.settings';

  protected function getEditableConfigNames()
  {
    return [static::SETTINGS];
  }

  public function getFormId()
  {
    return 'jix_interface_posting_plans_page_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config(static::SETTINGS);
    $form['posting_plans_page_header'] = [
      '#type' => 'textarea',
      '#rows' => 5,
      '#title' => $this->t('Header text'),
      '#default_value' => $config->get('posting_plans_page_header'),
      '#description' => $this->t('Enter text that appears on the top of posting plans page')
    ];
    $form['posting_plans_page_footer'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Footer text'),
      '#format' => 'full_html',
      '#allowed_formats' => ['full_html'],
      '#default_value' => $config->get('posting_plans_page_footer.value'),
      '#description' => $this->t('Enter text that appears on the bottom of posting plans page')
    ];
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('posting_plans_page_header', $form_state->getValue('posting_plans_page_header'))
      ->set('posting_plans_page_footer', $form_state->getValue('posting_plans_page_footer'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
