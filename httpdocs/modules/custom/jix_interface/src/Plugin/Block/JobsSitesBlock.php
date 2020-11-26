<?php


namespace Drupal\jix_interface\Plugin\Block;


use Drupal;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\jix_interface\Form\SitesAndServicesForm;

/**
 * Class JobsSitesBlock
 * @package Drupal\jir_interface\Plugin\Block
 * @Block(
 *     id = "jobs_sites_block",
 *     admin_label = @Translation("Jobs Sites block"),
 *     category = @Translation("Custom Jix Blocks")
 * )
 */
class JobsSitesBlock extends BlockBase
{

  public function build()
  {
    $sitesStr = strval(Drupal::configFactory()->get(SitesAndServicesForm::SETTINGS)->get('our_sites'));
    $sites = empty($sitesStr) ? array() : explode('|', $sitesStr);
    return[
      '#theme' => 'jix_jobs_sites',
      '#sites' => $sites
    ];
  }

  // Needed because there is an event listener in JixSettings that clear this cache on config save.
  public function getCacheTags()
  {
    return Cache::mergeTags(parent::getCacheTags(), ['jobs_sites_block']);
  }
}
