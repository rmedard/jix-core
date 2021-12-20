<?php

namespace Drupal\jix_interface\EventSubscriber;

use Drupal;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Routing\RequestHelper;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\jix_settings\Form\SitesAndServicesForm;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

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
  #[ArrayShape([ConfigEvents::SAVE => "string", KernelEvents::RESPONSE => "string"])]
  public static function getSubscribedEvents(): array
  {
    return [
      ConfigEvents::SAVE => 'onConfigSave',
      KernelEvents::RESPONSE => 'onRequest'
    ];
  }

  public function onConfigSave(ConfigCrudEvent $crudEvent) {
    if ($crudEvent->getConfig()->getName() == SitesAndServicesForm::SETTINGS) {
      Drupal::service('cache_tags.invalidator')->invalidateTags(['services_block']); // Clear services block cache after modification
      Drupal::service('cache_tags.invalidator')->invalidateTags(['jobs_sites_block']);
    }
  }

  public function onRequest(ResponseEvent $requestEvent) {
    if (!RequestHelper::isCleanUrl($requestEvent->getRequest())) {
      Drupal::logger('jix_interface')->warning('Invalid Url with /index.php detected.');
      $cleanRequestUri = $this->cleanPath($requestEvent->getRequest()->getRequestUri());
      Drupal::logger('jix_interface')->warning('Clean Url: ' . $cleanRequestUri . ' | Type: ' . $requestEvent->getRequestType());
      $response = new TrustedRedirectResponse($cleanRequestUri, 302);
      $response->headers->set('X-Drupal-Route-Normalizer', 1);
      $requestEvent->setResponse($response);
    }
  }

  private function cleanPath($pathStr): string {
    if (str_starts_with($pathStr, '/index.php')) {
      return $this->cleanPath(substr_replace($pathStr, '', 0, 10));
    }
    return $pathStr;
  }
}
