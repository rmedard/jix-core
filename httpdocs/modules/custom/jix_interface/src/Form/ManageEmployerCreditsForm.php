<?php

namespace Drupal\jix_interface\Form;

use Drupal;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Request;

class ManageEmployerCreditsForm extends FormBase {

  protected string $channel = 'Credits Form';

  protected NodeInterface $employer;

  public function __construct() {
    $node = Drupal::routeMatch()->getParameter('node');
    if (isset($node) and $node instanceof NodeInterface && $node->bundle() == 'employer') {
      $this->employer = $node;
    }
  }


  public function getFormId(): string {
    return 'employer_credits_manager_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['top_up_amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Top-up'),
      '#title_display' => 'before',
      '#default_value' => 0,
      '#min' => 0,
      '#description' => $this->t('Increment this employer\'s credit balance'),
      '#ajax' => [
        'callback' => [$this, 'incrementCredits'],
        'disable-refocus' => FALSE,
        'event' => 'keyup change',
        'wrapper' => 'edit-output',
        'progress' => [
          'type' => 'throbber'
        ],
      ],
    ];
    $form['alert_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Alert threshold'),
      '#title_display' => 'before',
      '#default_value' => $this->employer->get('field_employer_cr_al_threshold')->getString(),
      '#min' => 0,
      '#description' => $this->t('An email alert will be sent to the admin when this threshold is reached'),
      '#ajax' => [
        'callback' => [$this, 'incrementThreshold'],
        'disable-refocus' => FALSE,
        'event' => 'keyup change',
        'wrapper' => 'edit-output',
        'progress' => [
          'type' => 'throbber'
        ],
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#attributes' => ['class' => ['w-100']],
    ];
    return $form;
  }

  public function incrementCredits(array &$form, FormStateInterface $form_state, Request $request): AjaxResponse {
    return $this->increment('top_up_amount', '#edit-top-up-amount', $form_state);
  }

  public function incrementThreshold(array &$form, FormStateInterface $form_state, Request $request): AjaxResponse {
    return $this->increment('alert_threshold', '#edit-alert-threshold', $form_state);
  }

  private function increment(string $field_name, string $field_selector, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    if ($this->isPositiveInteger($form_state->getValue($field_name))) {
      $response->addCommand(new InvokeCommand($field_selector, 'removeClass', ['error is-invalid']));
      $response->addCommand(new InvokeCommand($field_selector, 'addClass', ['valid is-valid']));
    }
    else {
      $response->addCommand(new InvokeCommand($field_selector, 'removeClass', ['valid is-valid']));
      $response->addCommand(new InvokeCommand($field_selector, 'addClass', ['error is-invalid']));
    }
    return $response;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->hasValue('top_up_amount') or !$this->isPositiveInteger($form_state->getValue('top_up_amount'))) {
      $form_state->setErrorByName('top_up_amount', t('Credits top-up must be greater than 0'));
    }

    if (!$form_state->hasValue('alert_threshold') or !$this->isPositiveInteger($form_state->getValue('alert_threshold'))) {
      $form_state->setErrorByName('alert_threshold', t('Alert threshold must be at least 0'));
    }

    parent::validateForm($form, $form_state);
  }

  private function isPositiveInteger(mixed $value): bool {
    if (filter_var($value, FILTER_VALIDATE_INT) !== FALSE) {
      return intval($value) >= 0;
    }
    return FALSE;
  }


  public function submitForm(array &$form, FormStateInterface $form_state) {
    $topUp = trim($form_state->getValue('top_up_amount'));
    $alertThreshold = trim($form_state->getValue('alert_threshold'));
    try {
      $credits = intval($this->employer->get('field_employer_credits')->value) + intval($topUp);
      $this->employer->set('field_employer_credits', $credits);
      $this->employer->set('field_employer_cr_al_threshold', $alertThreshold);
      $this->employer->save();
      Drupal::logger($this->channel)->info('TopUp: @topUp | Threshold: @threshold', [
          '@topUp' => $topUp,
          '@threshold' => $alertThreshold,
        ]);
    } catch (EntityStorageException $e) {
      Drupal::logger($this->channel)
        ->error('Saving credits for employer @id failed', ['@id' => $this->employer->id()]);
    }
  }

}
