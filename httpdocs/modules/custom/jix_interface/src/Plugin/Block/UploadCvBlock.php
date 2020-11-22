<?php


namespace Drupal\jix_interface\Plugin\Block;


use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;

/**
 * Class AddressBlock
 * @package Drupal\jir_blocks\Plugin\Block
 * @Block(
 *     id = "upload_cv_block",
 *     admin_label = @Translation("Upload CV Block"),
 *     category = @Translation("Custom Jix Blocks")
 * )
 */
class UploadCvBlock extends BlockBase
{

  public function build()
  {
    return[
      '#label' => t('Upload CV'),
      '#theme' => 'jix_upload_cv'
    ];
  }
}
