<?php


namespace Drupal\jix_interface\Plugin\Block;


use Drupal;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\jix_interface\Form\SitesAndServicesForm;

/**
 * Class ServicesBlock
 * @package Drupal\jir_blocks\Plugin\Block
 * @Block(
 *     id = "services_block",
 *     admin_label = @Translation("Services block"),
 *     category = @Translation("Custom Jix Blocks")
 * )
 */
class ServicesBlock extends BlockBase
{

  public function build()
  {
    $servicesStr = strval(Drupal::configFactory()->get(SitesAndServicesForm::SETTINGS)->get('our_services'));
    $services = empty($servicesStr) ? array() : explode('|', $servicesStr);
    return[
      '#theme' => 'jix_services',
      '#services' => $services
    ];
  }

  // Needed because there is an event listener in JixSettings that clear this cache on config save.
  public function getCacheTags()
  {
    return Cache::mergeTags(parent::getCacheTags(), ['services_block']);
  }
}