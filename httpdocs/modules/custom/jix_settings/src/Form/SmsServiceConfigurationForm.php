<?php


namespace Drupal\jix_settings\Form;


use Drupal;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use phpseclib3\Net\SFTP;

class SmsServiceConfigurationForm extends ConfigFormBase
{

  const SETTINGS = 'jix_settings.sms.settings';

  protected function getEditableConfigNames(): array
  {
    return [static::SETTINGS];
  }

  public function getFormId(): string
  {
    return 'jix_sms_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $config = $this->config(static::SETTINGS);
    $form['number_daily_jobs'] = array(
      '#type' => 'number',
      '#title' => $this->t('Number of jobs'),
      '#default_value' => $config->get('number_daily_jobs'),
      '#required' => true,
      '#description' => $this->t('Number of job sms files to generate at a time. Must be between 1 and 5.')
    );
    $form['ftp_settings'] = array(
      '#title' => $this->t('FTP Settings'),
      '#type' => 'fieldset',
      '#collapsible' => false,
      '#collapsed' => false,
    );
    $form['ftp_settings']['ftp_protocol'] = [
      '#type' => 'select',
      '#title' => $this->t('Protocol'),
      '#options' => ['ftp' => 'FTP', 'sftp' => 'SFTP'],
      '#default_value' => $config->get('ftp_protocol'),
      '#required' => true,
      '#description' => $this->t('File transfer protocol')
    ];
    $form['ftp_settings']['ftp_host'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Host'),
      '#default_value' => $config->get('ftp_host'),
      '#required' => true,
      '#description' => $this->t('Host server name')
    );
    $form['ftp_settings']['ftp_port'] = array(
      '#type' => 'number',
      '#title' => $this->t('Port'),
      '#default_value' => $config->get('ftp_port'),
      '#required' => true,
      '#description' => $this->t('Host server port')
    );
    $form['ftp_settings']['ftp_directory'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Directory'),
      '#default_value' => empty($config->get('ftp_directory')) ? '/' : $config->get('ftp_directory'),
      '#required' => true,
      '#description' => $this->t('Directory path')
    );
    $form['ftp_settings']['ftp_username'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $config->get('ftp_username'),
      '#description' => $this->t('Server username. Leave blank if not required')
    );
    $form['ftp_settings']['ftp_password'] = array(
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#default_value' => $config->get('ftp_password'),
      '#description' => $this->t('The currently set password is hidden for security reasons.')
    );
    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    if ($form_state->isValueEmpty('number_daily_jobs')) {
      $form_state->setErrorByName('number_daily_jobs', t('Number of jobs cannot be empty or 0'));
    } else {
      if ($form_state->getValue('number_daily_jobs') > 5) {
        $form_state->setErrorByName('number_daily_jobs', t('Number of jobs must be between 1 and 5'));
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $protocol = $form_state->getValue('ftp_protocol');
    $host = $form_state->getValue('ftp_host');
    $port = intval($form_state->getValue('ftp_port'));
    $directory = $form_state->getValue('ftp_directory');
    $username = $form_state->getValue('ftp_username');
    $password = $form_state->getValue('ftp_password');
    if (empty($password)) {
      $password = Drupal::config(static::SETTINGS)->get('ftp_password');
    }

    $this->configFactory->getEditable(static::SETTINGS)
      ->set('number_daily_jobs', $form_state->getValue('number_daily_jobs'))
      ->set('ftp_protocol', $protocol)
      ->set('ftp_host', $host)
      ->set('ftp_port', $port)
      ->set('ftp_directory', $directory)
      ->set('ftp_username', $username)
      ->set('ftp_password', $password)
      ->save();
    parent::submitForm($form, $form_state);

    switch ($protocol) {
      case 'ftp':
        $connection = ftp_connect($host, $port, 30);
        if ($connection !== false) {
          if (ftp_login($connection, $username, $password)) {
            Drupal::messenger()->addStatus('Connection to FTP server is successful...');
            if (ftp_chdir($connection, $directory) === false) {
              Drupal::messenger()->addWarning('Directory folder not found.');
            }
          } else {
            Drupal::messenger()->addWarning('Connection to FTP server could not be verified...');
          }
        }
        break;
      case 'sftp':
        $sftp = new SFTP($host, $port);
        $loggedIn = $sftp->login($username, $password);
        if ($loggedIn) {
          Drupal::messenger()->addStatus('Connection to SFTP server is successful...');
          if ($sftp->chdir($directory) === false) {
            Drupal::messenger()->addWarning('Directory folder not found.');
          }
          if ($sftp->isConnected()) {
            $sftp->disconnect();
          }
        } else {
          Drupal::messenger()->addWarning('Connection to SFTP server could not be verified...');
        }
        break;
    }
  }
}
