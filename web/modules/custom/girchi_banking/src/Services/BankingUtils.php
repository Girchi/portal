<?php

namespace Drupal\girchi_banking\Services;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\om_tbc_payments\Services\PaymentService;

/**
 * Class BankingUtils.
 */
class BankingUtils {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Drupal\om_tbc_payments\Services\PaymentService definition.
   *
   * @var \Drupal\om_tbc_payments\Services\PaymentService
   */
  protected $omTbcPaymentsPaymentService;

  /**
   * Constructs a new BankingUtils object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   EM.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger.
   * @param \Drupal\om_tbc_payments\Services\PaymentService $om_tbc_payments_payment_service
   *   Omedia TBC payment service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory, PaymentService $om_tbc_payments_payment_service) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
    $this->omTbcPaymentsPaymentService = $om_tbc_payments_payment_service;
  }

  /**
   * Prepare credit card.
   *
   * @param string $trans_id
   *   TransactionID.
   * @param string $card_id
   *   Card ID for TBC execution.
   *
   * @return mixed
   *   Card or NULL.
   */
  public function prepareCard($trans_id, $card_id) {
    try {
      $card_storage = $this->entityTypeManager->getStorage('credit_card');
      if ($trans_id && $card_id) {
        $card = $card_storage->create([
          'trans_id' => $trans_id,
          'tbc_id' => $card_id,
          'status' => 'INITIAL',
        ]);
        return $card;
      }
      else {
        return NULL;
      }
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->get('girchi_banking')->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->get('girchi_banking')->error($e->getMessage());
    }

    return NULL;
  }

}
