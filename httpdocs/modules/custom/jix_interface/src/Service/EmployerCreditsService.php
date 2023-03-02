<?php

namespace Drupal\jix_interface\Service;

use Drupal;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\jix_notifier\Utils\EmailData;
use Drupal\jix_notifier\Utils\NotificationType;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

class EmployerCreditsService {

  public function consumeCredit(Node $employer): void {
    $credits = $employer->get('field_employer_credits')->value;
    if (isset($credits) and filter_var($credits, FILTER_VALIDATE_INT)) {
      if (intval($credits) > 0) {
        try {
          $credits = intval($credits) - 1;
          $employer->set('field_employer_credits', $credits);
          $employer->save();
          if ($this->needsToAlert($employer)) {
            $emailService = Drupal::service('jix_notifier.email_service');
            $emailService->send(new EmailData(NotificationType::CREDIT_THRESHOLD_REACHED, $employer));
          }
        } catch (EntityStorageException $e) {
          Drupal::logger('jix_interface')
            ->error('Consuming credit for employer @id failed', ['@id' => $employer->id()]);
        }
      }
    }
  }

  private function needsToAlert(Node $employer): bool {
    return $employer->get('field_employer_credits')
        ->getString() == $employer->get('field_employer_cr_al_threshold')
        ->getString();
  }

}
