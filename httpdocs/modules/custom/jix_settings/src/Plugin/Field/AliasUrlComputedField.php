<?php

namespace Drupal\jix_settings\Plugin\Field;

use Drupal;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

class AliasUrlComputedField extends FieldItemList
{
  use ComputedItemListTrait;

  protected function computeValue()
  {
    $alias = '';
    $adaptor = $this->parent;
    if ($adaptor instanceof EntityAdapter) {
      $nid = $adaptor->getEntity()->id();
      $alias = Drupal::request()->getSchemeAndHttpHost();
      $alias .= Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $nid);
    }
    $this->list[0] = $this->createItem(0, $alias);
  }
}
