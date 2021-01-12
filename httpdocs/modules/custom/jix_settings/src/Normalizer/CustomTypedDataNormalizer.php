<?php


namespace Drupal\jix_settings\Normalizer;


use Drupal;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\serialization\Normalizer\TypedDataNormalizer;

class CustomTypedDataNormalizer extends TypedDataNormalizer
{
  /**
   * @var LoggerChannelFactoryInterface
   */
  private $loggerChannelFactory;

  /**
   * CustomTypedDataNormalizer constructor.
   * @param LoggerChannelFactoryInterface $loggerChannelFactory
   */
  public function __construct(LoggerChannelFactoryInterface $loggerChannelFactory)
  {
    $this->loggerChannelFactory = $loggerChannelFactory;
  }


  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = null, array $context = [])
  {
    $this->loggerChannelFactory->get('default')->debug('Normalizer called');
    Drupal::logger('jix_settings')->warning('Normalizer called');
//    $values = $object->getValue();
//    if (isset($values[0]) && isset($values[0]['value'])) {
//      $values = $values[0]['value'];
//    }
//    return $values;

    $data = parent::normalize($object, $format, $context);
    // transform your data here
    // You'll likely need to run some checks on the $entity or $data
    // variables and include conditionals so that only the items
    // you are interested in are altered
    return $data;
  }
}
