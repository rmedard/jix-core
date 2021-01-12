<?php


use Composer\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\jix_settings\Form\SitesAndServicesForm;

/**
 * Class ConfigEventsSubscriber
 */
class ConfigEventsSubscriber implements EventSubscriberInterface
{

  /**
   * Returns an array of event names this subscriber wants to listen to.
   *
   * The array keys are event names and the value can be:
   *
   *  * The method name to call (priority defaults to 0)
   *  * An array composed of the method name to call and the priority
   *  * An array of arrays composed of the method names to call and respective
   *    priorities, or 0 if unset
   *
   * For instance:
   *
   *  * ['eventName' => 'methodName']
   *  * ['eventName' => ['methodName', $priority]]
   *  * ['eventName' => [['methodName1', $priority], ['methodName2']]]
   *
   * @return array The event names to listen to
   */
  public static function getSubscribedEvents(): array
  {
    return [
      ConfigEvents::SAVE => 'onConfigSave'
    ];
  }

  public function onConfigSave(ConfigCrudEvent $crudEvent) {
    if ($crudEvent->getConfig()->getName() == SitesAndServicesForm::SETTINGS) {
      Drupal::service('cache_tags.invalidator')->invalidateTags(['services_block']); // Clear services block cache after modification
      Drupal::service('cache_tags.invalidator')->invalidateTags(['jobs_sites_block']);
    }
  }
}
