<?php


namespace Drupal\jix_notifier\Plugin\RulesAction;


use Drupal;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;
use Drupal\rules\Core\Annotation\RulesAction;
use Drupal\rules\Core\RulesActionBase;
use Drupal\webform\Entity\WebformSubmission;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class UnsubscribeUserAction
 * @package Drupal\jix_notifier\Plugin\RulesAction
 *
 * @RulesAction(
 *     id = "rules_action_unsubscribe_user",
 *     label = @Translation("Unsubscribe User Action"),
 *     category = @Translation("Jix Custom Actions"),
 *     context_definitions = {
 *      "entity" = @ContextDefinition("entity:webform_submission", label = @Translation("Submission object"), description = @Translation("Submitted data"))
 *     }
 * )
 */
class UnsubscribeUserAction extends RulesActionBase
{

  private $channel;

  /**
   * Overrides \Drupal\Component\Plugin\PluginBase::__construct().
   *
   * Overrides the construction of context aware plugins to allow for
   * unvalidated constructor based injection of contexts.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->channel = 'jix_notifier';
  }

  protected function doExecute(EntityInterface $entity)
  {
    if ($entity instanceof WebformSubmission) {
      $email = $entity->getElementData('gen_news_email');
      $config = Drupal::config('jix_notifier.general.settings');
      $subscriptionUrl = $config->get('general_newsletter_url');
      try {
        $response = Drupal::httpClient()->delete($subscriptionUrl, array('json' => array('email' => $email)));
        if ($response instanceof ResponseInterface) {
          Drupal::logger($this->channel)->info('Response code: ' . $response->getStatusCode()
            . ' | Phrase: ' . $response->getBody()->getContents());
        }
      } catch (ClientException $exception) {
        Drupal::logger($this->channel)->error(Json::encode($exception));
      }
    }
  }
}
