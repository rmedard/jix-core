<?php


namespace Drupal\jix_interface\Plugin\Block;


use Drupal;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Class AddressBlock
 * @package Drupal\jir_blocks\Plugin\Block
 * @Block(
 *     id = "job_employer_block",
 *     admin_label = @Translation("Job Employer Block"),
 *     category = @Translation("Custom Jix Blocks")
 * )
 */
class JobEmployerBlock extends BlockBase
{

  public function build(): array
  {
    $output = [];
    $output[]['#cache']['max-age'] = 0;

    $route_name = Drupal::routeMatch()->getRouteName();
    if ($route_name == 'entity.node.canonical') {
      $node = Drupal::routeMatch()->getParameter('node');
      if ($node instanceof NodeInterface and in_array($node->bundle(), ['job', 'employer'])) {
        $employer = [];
        $nodeBundle = '';
        if ($node->bundle() == 'job') {
          $nodeBundle = 'job';
          $employer = Node::load(intval($node->get('field_job_employer')->target_id));
        }
        elseif ($node->bundle() == 'employer') {
          $nodeBundle = 'employer';
          $employer = $node;
        }
        if ($employer instanceof NodeInterface and $employer->get('field_employer_logo')->isEmpty()) {
          $entityRepo = Drupal::service('entity.repository');
          if ($entityRepo instanceof EntityRepositoryInterface) {
            try {
              $employerLogoField = $employer->get('field_employer_logo')->getFieldDefinition();
              $defaultLogoImage = $entityRepo->loadEntityByUuid('file', $employerLogoField->getSetting('default_image')['uuid']);
              $employer->set('field_employer_logo', $defaultLogoImage);
            } catch (EntityStorageException $e) {
              Drupal::logger('jix_interface')->error($e->getMessage());
            }
          }
        }
        $output[] = [
          '#theme' => 'jix_job_employer',
          '#employer' => $employer,
          '#pageNodeBundle' => $nodeBundle
        ];
      }
    }
    return $output;
  }
}
