<?php


namespace Drupal\jix_interface\Service;


use Drupal;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

class StatisticsService
{

  public function countContentEntities($entityType): string
  {
    if ($entityType === 'job') {
      $statsUrl = Drupal::config('jix_settings.website.info')->get('stats_url');
      if (!empty($statsUrl)) {
        return '0'; //Awaits JS to update
      }
    }

    try {
      $storage = Drupal::entityTypeManager()->getStorage('node');
      $jobs_count = $storage->getQuery()
        ->accessCheck(false)
        ->condition('type', $entityType)->count()->execute();
      return number_format($jobs_count);
    } catch (InvalidPluginDefinitionException $e) {
      Drupal::logger('jix_interface')->error('Invalid plugin: ' . $e->getMessage());
    } catch (PluginNotFoundException $e) {
      Drupal::logger('jix_interface')->error('Plugin not found: ' . $e->getMessage());
    }
    return '0';
  }

  public function countJobSubmissions(): string
  {
    $statsUrl = Drupal::config('jix_settings.website.info')->get('stats_url');
    if (empty($statsUrl)) { // Else, waits for JS to update
      try {
        $storage = Drupal::entityTypeManager()->getStorage('webform_submission');
        $submissions_count = $storage->getQuery()
          ->accessCheck(false)
          ->condition('webform_id', 'default_job_application_form')
          ->count()->execute();
        return number_format($submissions_count);
      } catch (InvalidPluginDefinitionException $e) {
        Drupal::logger('jix_interface')->error('Invalid plugin: ' . $e->getMessage());
      } catch (PluginNotFoundException $e) {
        Drupal::logger('jix_interface')->error('Plugin not found: ' . $e->getMessage());
      }
    }
    return '0';
  }
}
