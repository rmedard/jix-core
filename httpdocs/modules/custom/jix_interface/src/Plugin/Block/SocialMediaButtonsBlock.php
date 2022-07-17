<?php


namespace Drupal\jix_interface\Plugin\Block;


use Drupal;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\jix_settings\Form\SocialMediaSettingsForm;
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

  #[ArrayShape(['#theme' => "string", '#facebook' => "string", '#twitter' => "string", '#instagram' => "string", '#youtube' => "string", '#linkedin' => "string"])] public function build(): array
  {
    $facebook = Drupal::config(SocialMediaSettingsForm::SETTINGS)->get('facebook_page');
    $instagram = Drupal::config(SocialMediaSettingsForm::SETTINGS)->get('instagram_page');
    $twitter = Drupal::config(SocialMediaSettingsForm::SETTINGS)->get('twitter_page');
    $youtube = Drupal::config(SocialMediaSettingsForm::SETTINGS)->get('youtube_page');
    $linkedin = Drupal::config(SocialMediaSettingsForm::SETTINGS)->get('linkedin_page');
    return [
      '#theme' => 'jix_social_media_buttons',
      '#facebook' => trim($facebook),
      '#twitter' => trim($twitter),
      '#instagram' => trim($instagram),
      '#youtube' => trim($youtube),
      '#linkedin' => trim($linkedin)
    ];
  }
}
