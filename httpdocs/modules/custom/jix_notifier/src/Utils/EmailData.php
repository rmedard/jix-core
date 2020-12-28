<?php


namespace Drupal\jix_notifier\Utils;


use Drupal\node\NodeInterface;

class EmailData
{
  private $notificationType;
  private $entity;

  /**
   * EmailData constructor.
   * @param $notificationType
   * @param $entity
   */
  public function __construct($notificationType, $entity)
  {
    $this->notificationType = $notificationType;
    $this->entity = $entity;
  }


  function getNotificationType(): string {
    return $this->notificationType;
  }

  function getEntity(): NodeInterface {
    return $this->entity;
  }
}
