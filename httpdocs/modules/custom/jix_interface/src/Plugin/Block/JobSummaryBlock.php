<?php


namespace Drupal\jix_interface\Plugin\Block;


use Drupal;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\node\NodeInterface;

/**
 * Class AddressBlock
 * @package Drupal\jix_interface\Plugin\Block
 * @Block(
 *     id = "job_summary_block",
 *     admin_label = @Translation("Job Summary Block"),
 *     category = @Translation("Custom Jix Blocks")
 * )
 */
class JobSummaryBlock extends BlockBase
{

  public function build()
  {
    $output = [];
    $output[]['#cache']['max-age'] = 0;
    $route_name = Drupal::routeMatch()->getRouteName();
    if ($route_name == 'entity.node.canonical') {
      $job = Drupal::routeMatch()->getParameter('node');
      if ($job instanceof NodeInterface && $job->bundle() == 'job') {
        $stats_storage = Drupal::service('statistics.storage.node');
        $viewsCount = 1;
        if ($stats_storage->fetchView($job->id()) !== false) {
          $viewsCount = $stats_storage->fetchView($job->id())->getTotalCount();
        }
        $output[] = [
          '#theme' => 'jix_job_summary',
          '#job' => $job,
          '#viewsCount' => $viewsCount
        ];
      }
    }
    return $output;
  }

  public function getCacheMaxAge()
  {
    return 0;
  }
}
