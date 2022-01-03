<?php


namespace Drupal\jix_settings\Form;


use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Locale\CountryManager;

class WebsiteSettingsForm extends ConfigFormBase
{

  const SETTINGS = 'jix_settings.website.info';

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames(): array
  {
    return [static::SETTINGS];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId(): string
  {
    return 'jix_settings_website';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $config = $this->config(static::SETTINGS);
    $countries = array_map(function ($standardCountry) {
      return $standardCountry->getUntranslatedString();
    }, CountryManager::getStandardList());

    $form['site_target_country'] = [
      '#type' => 'select',
      '#title' => $this->t('Project target country'),
      '#options' => $countries,
      '#empty_option' => $this->t('-No specific country-'),
      '#default_value' => $config->get('site_target_country')
    ];
    $form['site_owner'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site owner'),
      '#default_value' => $config->get('site_owner')
    ];
    $form['site_address_line_1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site address line 1'),
      '#default_value' => $config->get('site_address_line_1')
    ];
    $form['site_address_line_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site address line 2'),
      '#default_value' => $config->get('site_address_line_2')
    ];
    $form['site_phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Site phone'),
      '#default_value' => $config->get('site_phone')
    ];
    $form['stats_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Statistics data URL'),
      '#default_value' => $config->get('stats_url')
    ];

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    if (!$form_state->isValueEmpty('stats_url')) {
      $isValid = filter_var($form_state->getValue('stats_url'), FILTER_VALIDATE_URL);
      if ($isValid === false) {
        $form_state->setErrorByName('stats_url', t('This must be a valid URL starting with \'http\' or \'https\''));
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('site_target_country', $form_state->getValue('site_target_country'))
      ->set('site_owner', $form_state->getValue('site_owner'))
      ->set('site_address_line_1', $form_state->getValue('site_address_line_1'))
      ->set('site_address_line_2', $form_state->getValue('site_address_line_2'))
      ->set('site_phone', $form_state->getValue('site_phone'))
      ->set('stats_url', $form_state->getValue('stats_url'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
