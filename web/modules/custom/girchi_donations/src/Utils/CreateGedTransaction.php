<?php

namespace Drupal\girchi_donations\Utils;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Service to create gedtransaction.
 */
class CreateGedTransaction {

  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * GedCalculator.
   *
   * @var GedCalculator
   */
  protected $gedCalculator;

  /**
   * Logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerChannelFactory;

  /**
   * CreateGedTransaction constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   EntityTypeManager.
   * @param GedCalculator $gedCalculator
   *   GedCalculator.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerChannelFactory
   *   Logger factory.
   */
  public function __construct(EntityTypeManager $entityTypeManager, GedCalculator $gedCalculator, LoggerChannelFactory $loggerChannelFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->gedCalculator = $gedCalculator;
    $this->loggerChannelFactory = $loggerChannelFactory->get('girchi_donation');
  }

  /**
   * CreateGedtransaciton.
   *
   * @param mixed $currentUserId
   *   Current user id.
   * @param mixed $amount
   *   Amount of money.
   *
   * @return mixed
   *   Ged transaction id
   */
  public function createGedTransaction($currentUserId, $amount) {
    try {
      $transaction_type_id = $this->entityTypeManager->getStorage('taxonomy_term')->load(1369) ? '1369' : NULL;
      $ged_manager = $this->entityTypeManager->getStorage('ged_transaction');

      $ged_amount = $this->gedCalculator->calculate($amount);
      /** @var \Drupal\girchi_ged_transactions\Entity\GedTransaction $transaction */
      $transaction = $ged_manager->create([
        'user_id' => "1",
        'user' => $currentUserId,
        'ged_amount' => $ged_amount,
        'title' => 'Donation',
        'name' => 'Donation',
        'status' => TRUE,
        'Description' => 'Transaction was created by donation',
        'transaction_type' => $transaction_type_id,
      ]);

      $transaction->save();
      $this->loggerChannelFactory->info('GedTransaction was created for user : ' . $currentUserId);
      return $transaction->id();
    }
    catch (\Exception $e) {
      $this->loggerChannelFactory->error($e->getMessage());
    }
  }

}
