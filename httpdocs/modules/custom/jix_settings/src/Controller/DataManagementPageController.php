<?php


namespace Drupal\jix_settings\Controller;


use Drupal\Core\Controller\ControllerBase;

class DataManagementPageController extends ControllerBase
{
  public function content(): array
  {
    return [
      '#theme' => 'jix_data_manager'
    ];
  }
}
