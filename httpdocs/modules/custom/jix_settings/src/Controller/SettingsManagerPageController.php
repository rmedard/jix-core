<?php


namespace Drupal\jix_settings\Controller;


use Drupal\Core\Controller\ControllerBase;
use JetBrains\PhpStorm\ArrayShape;

/**
 * Class SettingsManagerPageController
 * @package Drupal\jix_settings\Controller
 */
class SettingsManagerPageController extends ControllerBase
{
    #[ArrayShape(['#theme' => "string"])]
    public function content(): array
    {
      return [
          '#theme' => 'jix_settings_manager'
      ];
    }
}
