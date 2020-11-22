<?php


namespace Drupal\jix_settings\Form;


use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

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
    protected function getEditableConfigNames()
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
    public function getFormId()
    {
        return 'jix_settings_website';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state){
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

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state){
        $this->configFactory->getEditable(static::SETTINGS)
            ->set('site_owner', $form_state->getValue('site_owner'))
            ->set('site_address_line_1', $form_state->getValue('site_address_line_1'))
            ->set('site_address_line_2', $form_state->getValue('site_address_line_2'))
            ->set('site_phone', $form_state->getValue('site_phone'))
            ->save();
        parent::submitForm($form, $form_state);
    }
}