<?php


namespace Drupal\jix_interface\Plugin\Block;


use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use JetBrains\PhpStorm\ArrayShape;

/**
 * Class CopyrightBlock
 * @package Drupal\jir_interface\Plugin\Block
 *
 * @Block(
 *     id = "copyright_block",
 *     admin_label = @Translation("Copyright block"),
 *     category = @Translation("Custom Jix Blocks")
 * )
 */
class CopyrightBlock extends BlockBase
{

  #[ArrayShape(['#theme' => "string"])]
  public function build(): array
  {
    return [
      '#theme' => 'jix_copyright',
    ];
  }
}
