<?php

namespace Drupal\girchi_donations\Utils;

use ABGEO\NBG\Exception\InvalidCurrencyException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * GeD Calculator.
 */
class GedCalculator {
  /**
   * EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * LoggerFactory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * KeyValue.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactory
   */
  protected $keyValue;

  /**
   * Constructor for service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity Type Manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactory $keyValue
   *   KeyValue.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              LoggerChannelFactoryInterface $loggerFactory,
                              KeyValueFactory $keyValue) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $loggerFactory;
    $this->keyValue = $keyValue->get('girchi_donations');
  }

  /**
   * Main function.
   *
   * @param int $amount
   *   Amount of GEL.
   *
   * @return int
   *   Return GED equivalent of amount.
   */
  public function calculate($amount) {
    try {
      $currency = $this->getCurrency();
      $ged_amount = $amount / $currency * 100;

      return (int) ceil($ged_amount);
    }
    catch (InvalidCurrencyException $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());
    }

    return NULL;

  }

  /**
   * Return int.
   */
  public function getCurrency() {
    $currency = $this->keyValue->get('usd');
    return $currency;
  }

}
