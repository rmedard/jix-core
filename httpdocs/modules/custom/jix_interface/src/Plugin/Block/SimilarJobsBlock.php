<?php


namespace Drupal\jix_interface\Plugin\Block;


use Drupal;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Class SimilarJobsBlock
 * @package Drupal\jir_interface\Plugin\Block
 * @Block(
 *     id = "similar_jobs_block",
 *     admin_label = @Translation("Similar Jobs block"),
 *     category = @Translation("Custom Jix Blocks")
 * )
 */
class SimilarJobsBlock extends BlockBase
{

  public function build(): array
  {
    $output = [];
    $output[]['#cache']['max-age'] = 0;

    $route_name = Drupal::routeMatch()->getRouteName();
    if ($route_name == 'entity.node.canonical') {
      $node = Drupal::routeMatch()->getParameter('node');
      if ($node instanceof NodeInterface and $node->bundle() === 'job') {
        try {
          $storage = Drupal::entityTypeManager()->getStorage('node');
          $query = $storage->getQuery()->range(0, 5);
          $query = $query
            ->condition('type', 'job')
            ->condition('status', Node::PUBLISHED)
            ->condition('nid', $node->id(), '<>')
            ->condition('field_job_offer_type', $node->get('field_job_offer_type')->value);
          $categories = $node->get('field_job_category')->getValue();
          if (!empty($categories)) {
            $or = $query->orConditionGroup();
            foreach ($categories as $category) {
              $or->condition('field_job_category.target_id', $category, 'IN');
            }
            $and = $query->andConditionGroup();
            $and->condition($or);
            $query->condition($and);
          }
          $jobIds = $query->execute();
          if (!empty($jobIds)) {
            $output[] = [
              '#theme' => 'jix_similar_jobs',
              '#jobs' => $storage->loadMultiple($jobIds)
            ];
          }
        } catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
          Drupal::logger('jir_interface')->error($e->getMessage());
        }
      }
    }
    return $output;
  }
}
