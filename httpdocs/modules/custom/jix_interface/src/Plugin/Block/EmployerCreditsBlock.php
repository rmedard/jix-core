<?php

namespace Drupal\jix_interface\Plugin\Block;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * @Block(
 *   id = "employer_credits_block",
 *   admin_label = @Translation("Employer Credits Block"),
 *   category = @Translation("Custom Jix Blocks")
 * )
 */
class EmployerCreditsBlock extends BlockBase {

  protected AccountInterface $account;

  public function access(AccountInterface $account, $return_as_object = FALSE): bool|AccessResult {
    return AccessResult::allowedIf($account->isAuthenticated());
  }

  public function label() {
    return $this->t('Employer credits');
  }

  public function getEmployer(): NodeInterface|array {
    $node = Drupal::routeMatch()->getParameter('node');
    if (isset($node) and $node instanceof NodeInterface && $node->bundle() == 'employer') {
      return $node;
    }
    return [];
  }

  /**
   * @return array
   */
  public function build(): array {
    $output = [];
    $output[]['#cache']['max-age'] = 0;
    $employer = $this->getEmployer();
    if ($employer instanceof NodeInterface) {
      $form = Drupal::formBuilder()->getForm('Drupal\jix_interface\Form\ManageEmployerCreditsForm');
      $form['#title'] = $this->t('Employer credits <span class="text-danger float-end">Balance: ' . $employer->get('field_employer_credits')->getString() .'</span>');
      $form['#attributes']['class'][] = 'block-webform';
      $form['#cache']['max-age'] = 0;
      return $form;
    }
    return $output;
  }
}
