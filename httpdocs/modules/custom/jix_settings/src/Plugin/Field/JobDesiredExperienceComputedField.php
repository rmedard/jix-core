<?php

namespace Drupal\jix_settings\Plugin\Field;

use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;

class JobDesiredExperienceComputedField extends FieldItemList
{
  use ComputedItemListTrait;

  protected function computeValue(): void
  {
    $experience = '';
    $adaptor = $this->parent;
    if ($adaptor instanceof EntityAdapter) {
      $job = $adaptor->getEntity();
      if ($job instanceof NodeInterface) {
        $term = $job->get('field_job_desired_experience')->entity;
        if ($term instanceof Term) {
          $experience = $term->getName();
        }
      }
    }
    $this->list[0] = $this->createItem(0, $experience);
  }
}
