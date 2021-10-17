<?php

namespace Drupal\jix_settings\Plugin\Field;

use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\node\NodeInterface;

class EmployerLogoUrlComputedField extends FieldItemList
{
  use ComputedItemListTrait;

  protected function computeValue()
  {
    $fileUri = '';
    $adaptor = $this->parent;
    if ($adaptor instanceof EntityAdapter) {
      $employer = $adaptor->getEntity();
      if ($employer instanceof NodeInterface) {
        $file = $employer->get('field_employer_logo')->entity;
        if ($file instanceof FileInterface) {
          $fileUri = $file->createFileUrl(false);
        }
      }
    }
    $this->list[0] = $this->createItem(0, $fileUri);
  }
}
