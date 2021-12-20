<?php


namespace Drupal\jix_settings\Controller;


use Drupal\Core\Controller\ControllerBase;
use JetBrains\PhpStorm\ArrayShape;

class DataManagementPageController extends ControllerBase
{
  #[ArrayShape(['#theme' => "string"])]
  public function content(): array
  {
    return [
      '#theme' => 'jix_data_manager'
    ];
  }
}
