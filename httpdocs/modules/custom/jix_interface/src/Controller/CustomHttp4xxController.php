<?php

namespace Drupal\jix_interface\Controller;

use Drupal;
use Drupal\node\NodeInterface;
use Drupal\system\Controller\Http4xxController;

class CustomHttp4xxController extends Http4xxController
{

  public function on403(): array
  {
    $modulePath = drupal_get_path('module', 'jix_interface');
    $templateFile = $modulePath . '/templates/error-403.html.twig';
    if (Drupal::routeMatch()->getRouteName() === 'jix_interface.403') {
      $node = Drupal::request()->attributes->get('node');
      if ($node instanceof NodeInterface && !$node->isPublished() && $node->bundle() === 'job') {
        $templateFile = $modulePath . '/templates/error-job-expired.html.twig';
      }
    }
    $template = Drupal::service('twig')->loadTemplate($templateFile);
    return [
      '#markup' => $template->render([])
    ];
  }
}
