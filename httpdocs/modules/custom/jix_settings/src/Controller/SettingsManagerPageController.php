<?php


namespace Drupal\jix_settings\Controller;


use Drupal\Core\Controller\ControllerBase;

/**
 * Class SettingsManagerPageController
 * @package Drupal\jix_settings\Controller
 */
class SettingsManagerPageController extends ControllerBase
{
    public function content(): array
    {
      return [
          '#theme' => 'jix_settings_manager'
      ];
    }
}
