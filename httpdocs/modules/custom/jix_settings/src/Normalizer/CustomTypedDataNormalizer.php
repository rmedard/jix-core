<?php


namespace Drupal\jix_settings\Normalizer;


use Drupal;
use Drupal\serialization\Normalizer\TypedDataNormalizer;

class CustomTypedDataNormalizer extends TypedDataNormalizer
{

//  protected $supportedInterfaceOrClass = TypedDataInterface::class;

//  public function denormalize($data, $type, $format = null, array $context = [])
//  {
//    $this->denormalize($data, $type, $format, $context);
//  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = null, array $context = [])
  {
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
