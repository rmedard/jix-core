<?php


namespace Drupal\jix_interface\Plugin\Block;


use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use JetBrains\PhpStorm\ArrayShape;

/**
 * Class SocialMediaButtonsBlock
 * @package Drupal\jix_interface\Plugin\Block
 * @Block(
 *     id = "social_media_buttons_block",
 *     admin_label = @Translation("Social Media Buttons Block"),
 *     category = @Translation("Custom Jix Blocks")
 * )
 */
class SocialMediaButtonsBlock extends BlockBase
{

  #[ArrayShape(['#theme' => "string"])] public function build(): array
  {
    return [
      '#theme' => 'jix_social_media_buttons'
    ];
  }
}
