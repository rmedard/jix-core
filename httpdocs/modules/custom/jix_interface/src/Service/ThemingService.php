<?php

namespace Drupal\jix_interface\Service;

use Drupal\node\Entity\Node;

class ThemingService
{
  public function getOfferTypePill(Node $job): string {
    $offer_type_field = $job->get('field_job_offer_type');
    $offer_values = $offer_type_field->getSetting('allowed_values');
    $offer_type = $offer_type_field->value;
    return match ($offer_type) {
      'job' => '<span class="badge badge-primary">' . $offer_values[$offer_type] . '</span>',
      'tender' => '<span class="badge badge-light">' . $offer_values[$offer_type] . '</span>',
      'consultancy' => '<span class="badge badge-success">' . $offer_values[$offer_type] . '</span>',
      'internship' => '<span class="badge badge-warning">' . $offer_values[$offer_type] . '</span>',
      'other' => '<span class="badge badge-info">' . $offer_values[$offer_type] . '</span>',
      default => '',
    };
  }
}
