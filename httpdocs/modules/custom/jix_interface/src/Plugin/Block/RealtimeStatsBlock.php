<?php


namespace Drupal\jix_interface\Plugin\Block;


use Drupal;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;

/**
 * Class AddressBlock
 * @package Drupal\jix_interface\Plugin\Block
 * @Block(
 *     id = "jix_realtime_stats",
 *     admin_label = @Translation("Realtime figures"),
 *     category = @Translation("Custom Jix Blocks")
 * )
 */
class RealtimeStatsBlock extends BlockBase
{

  public function build()
  {
    $statsService = Drupal::service('jix_interface.statistics_service');
    $output = [];
    $output[]['#cache']['max-age'] = 0;
    $output[] = [
      '#theme' => 'jix_realtime_stats',
      '#jobs_count' => $statsService->countContentEntities('job'),
      '#employers_count' => $statsService->countContentEntities('employer'),
      '#candidates_count' => $statsService->countJobSubmissions()
    ];
    return $output;
  }
}
