<?php

namespace Drupal\girchi_donations\Utils;

use ABGEO\NBG\Exception\InvalidCurrencyException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use ABGEO\NBG\Currency;

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
   * Currency.
   *
   * @var \ABGEO\NBG\Currency
   */
  public $USD;

  /**
   * Constructor for service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity Type Manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger.
   *
   * @throws \ABGEO\NBG\Exception\InvalidCurrencyException
   * @throws \SoapFault
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              LoggerChannelFactoryInterface $loggerFactory
                                  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $loggerFactory;
    $this->USD = new Currency(Currency::CURRENCY_USD);
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
      $currency = $this->USD->getCurrency();
      $ged_amount = $amount / $currency * 100;

      return (int) ceil($ged_amount);
    }
    catch (InvalidCurrencyException $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());
    }

    return NULL;

  }

  public function getCurrency(){
    return $this->USD->getCurrency();
  }

}
