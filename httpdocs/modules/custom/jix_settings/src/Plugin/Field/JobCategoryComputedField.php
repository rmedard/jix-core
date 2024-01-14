<?php

namespace Drupal\jix_settings\Plugin\Field;

use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;

class JobCategoryComputedField extends FieldItemList
{
  use ComputedItemListTrait;

  protected function computeValue(): void
  {
    $categories = '';
    $adaptor = $this->parent;
    if ($adaptor instanceof EntityAdapter) {
      $job = $adaptor->getEntity();
      if ($job instanceof NodeInterface) {
        foreach ($job->get('field_job_category')->getValue() as $category) {
          $categories .= Term::load(intval($category['target_id']))->getName() . ' ';
        }
        $categories = str_replace(' ', ', ', trim($categories));
      }
    }
    $this->list[0] = $this->createItem(0, $categories);
  }
}
