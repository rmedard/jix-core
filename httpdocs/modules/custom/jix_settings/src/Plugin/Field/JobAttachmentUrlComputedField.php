<?php

namespace Drupal\jix_settings\Plugin\Field;

use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\node\NodeInterface;

class JobAttachmentUrlComputedField extends FieldItemList
{
  use ComputedItemListTrait;

  protected function computeValue()
  {
    $fileUri = '';
    $adaptor = $this->parent;
    if ($adaptor instanceof EntityAdapter) {
      $job = $adaptor->getEntity();
      if ($job instanceof NodeInterface) {
        foreach ($job->get('field_job_attachment')->getValue() as $item) {
          $file = File::load(intval($item['target_id']));
          if ($file instanceof FileInterface) {
            $fileUri .= $file->createFileUrl(false) . ' ';
          }
        }
        $fileUri = str_replace(' ', ', ', trim($fileUri));
      }
    }
    $this->list[0] = $this->createItem(0, $fileUri);
  }
}
