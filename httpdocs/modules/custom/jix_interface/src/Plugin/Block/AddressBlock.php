<?php


namespace Drupal\jix_interface\Plugin\Block;


use Drupal;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;

/**
 * Class AddressBlock
 * @package Drupal\jir_interface\Plugin\Block
 * @Block(
 *     id = "address_block",
 *     admin_label = @Translation("Address block"),
 *     category = @Translation("Custom Jix Blocks")
 * )
 */
class AddressBlock extends BlockBase
{

  public function build(): array
  {
    $servicesStr = strval(Drupal::configFactory()->get('jix_interface.website.info')->get('our_services')); //TODO Finish this...
    return[
      '#theme' => 'jix_address'
    ];
  }
}
