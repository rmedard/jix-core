<?php

namespace Drupal\jix_interface\Service;

use Drupal\node\Entity\Node;

class ThemingService
{
  public function getOfferTypePill(Node $job): string {
    $offer_type_field = $job->get('field_job_offer_type');
    $offer_values = $offer_type_field->getSetting('allowed_values');
    $offer_type = $offer_type_field->value;
    $offer_value_pill = '';
    switch ($offer_type) {
      case 'job':
        $offer_value_pill = '<span class="badge badge-primary">' . $offer_values[$offer_type] . '</span>';
        break;
      case 'tender':
        $offer_value_pill = '<span class="badge badge-light">' . $offer_values[$offer_type] . '</span>';
        break;
      case 'consultancy':
        $offer_value_pill = '<span class="badge badge-success">' . $offer_values[$offer_type] . '</span>';
        break;
      case 'internship':
        $offer_value_pill = '<span class="badge badge-warning">' . $offer_values[$offer_type] . '</span>';
        break;
      case 'other':
        $offer_value_pill = '<span class="badge badge-info">' . $offer_values[$offer_type] . '</span>';
        break;
    }
    return $offer_value_pill;
  }
}
