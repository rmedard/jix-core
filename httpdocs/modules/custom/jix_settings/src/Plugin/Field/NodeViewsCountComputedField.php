<?php

namespace Drupal\jix_settings\Plugin\Field;

use Drupal;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

class NodeViewsCountComputedField extends FieldItemList
{
  use ComputedItemListTrait;

  protected function computeValue()
  {
    $viewsCount = 0;
    $adaptor = $this->parent;
    if ($adaptor instanceof EntityAdapter) {
      $stats_storage = Drupal::service('statistics.storage.node');
      $node_id = $adaptor->getEntity()->id();
      if ($stats_storage->fetchView($node_id) !== false) {
        $viewsCount = $stats_storage->fetchView($node_id)->getTotalCount();
      }
    }
    $this->list[0] = $this->createItem(0, intval($viewsCount));
  }
}
