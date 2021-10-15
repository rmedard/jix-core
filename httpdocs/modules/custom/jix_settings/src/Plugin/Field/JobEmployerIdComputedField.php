<?php

namespace Drupal\jix_settings\Plugin\Field;

use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\node\NodeInterface;

class JobEmployerIdComputedField extends FieldItemList
{
  use ComputedItemListTrait;

  protected function computeValue()
  {
    $employer_id = '';
    $adaptor = $this->parent;
    if ($adaptor instanceof EntityAdapter) {
      $job = $adaptor->getEntity();
      if ($job instanceof NodeInterface) {
        $employer = $job->get('field_job_employer')->entity;
        if ($employer instanceof NodeInterface) {
          $employer_id = $employer->uuid();
        }
      }
    }
    $this->list[0] = $this->createItem(0, $employer_id);
  }
}
