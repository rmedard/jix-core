<?php


namespace Drupal\jix_interface\Controller;


use Drupal;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\jix_interface\Form\PostingPlansPageSettingsForm;
use Drupal\node\NodeInterface;
use JetBrains\PhpStorm\ArrayShape;

class PagesController extends ControllerBase
{
  #[ArrayShape(['#theme' => "string", '#plans' => "array|\Drupal\Core\Entity\EntityInterface[]", '#header_text' => "array|mixed|null", '#footer_text' => "array|mixed|null"])]
  public function postingPlansPage(): array
  {
    $pricing_plans = [];
    try {
      $storage = Drupal::entityTypeManager()->getStorage('node');
      $query = $storage->getQuery()->range(0, 4)
        ->condition('type', 'pricing_plan')
        ->condition('status', NodeInterface::PUBLISHED)
        ->sort('field_pricing_plan_order_number', 'ASC');
      $planIds = $query->execute();
      if (!empty($planIds)) {
        $pricing_plans = $storage->loadMultiple(array_map('intval', array_values($planIds)));
      }
    } catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      Drupal::logger('jix_interface')->error($e->getMessage());
    }

    $config = Drupal::config(PostingPlansPageSettingsForm::SETTINGS);
    $headerText = $config->get('posting_plans_page_header');
    $footerText = $config->get('posting_plans_page_footer');
    return [
      '#theme' => 'jix_posting_plans_page',
      '#plans' => $pricing_plans,
      '#header_text' => $headerText,
      '#footer_text' => $footerText,
    ];
  }

  public function error404Page(): array {
    return [
      '#theme' => 'error_404'
    ];
  }
}
