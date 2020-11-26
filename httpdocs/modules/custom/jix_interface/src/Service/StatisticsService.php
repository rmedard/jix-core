<?php


namespace Drupal\jix_interface\Service;


use Drupal;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

class StatisticsService
{

  public function countContentEntities($entityType)
  {
    try {
      $storage = Drupal::entityTypeManager()->getStorage('node');
      return $storage->getQuery()->condition('type', $entityType)->count()->execute();
    } catch (InvalidPluginDefinitionException $e) {
      Drupal::logger('jix_interface')->error('Invalid plugin: ' . $e->getMessage());
    } catch (PluginNotFoundException $e) {
      Drupal::logger('jix_interface')->error('Plugin not found: ' . $e->getMessage());
    }
    return 0;
  }

}
