<?php

namespace Drupal\jix_settings\Form;


use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SitesAndServicesForm extends ConfigFormBase
{

    const SETTINGS = 'jix_settings.sites.services';

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
        return 'jix_settings_sites_services';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state){
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

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state){
        $this->configFactory->getEditable(static::SETTINGS)
            ->set('our_services', $form_state->getValue('our_services'))
            ->set('our_sites', $form_state->getValue('our_sites'))
            ->save();
        parent::submitForm($form, $form_state);
    }
}