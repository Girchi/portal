<?php

namespace Drupal\girchi_donations\Utils;

use Drupal\Core\Entity\EntityTypeManager;

/**
 *
 */
class CreateGedTransaction {
  /**
   *
   *
   *
   */

  protected $entityTypeManager;

  /**
   *
   *
   *
   */

  protected $gedCalculator;

  /**
   *
   */
  public function __construct(EntityTypeManager $entityTypeManager, GedCalculator $gedCalculator) {
    $this->entityTypeManager = $entityTypeManager;
    $this->gedCalculator = $gedCalculator;
  }

  /**
   * @param \Drupal\girchi_donations\Entity\Donation $donation
   */
  public function createGedTransaction($donation) {
    $transaction_type_id = $this->entityTypeManager->getStorage('taxonomy_term')->load(1369) ? '1369' : NULL;
    $ged_manager = $this->entityTypeManager->getStorage('ged_transaction');

    $user = $donation->getUser();
    $gel_amount = $donation->getAmount();
    $ged_amount = $this->gedCalculator->calculate($gel_amount);
    /** @var \Drupal\girchi_ged_transactions\Entity\GedTransaction $transaction */
    $transaction = $ged_manager->create([
      'user_id' => "1",
      'user' => $user->id(),
      'ged_amount' => $ged_amount,
      'title' => 'Donation',
      'name' => 'Donation',
      'status' => TRUE,
      'Description' => 'Transaction was created by donation',
      'transaction_type' => $transaction_type_id,
    ]);

    $transaction->save();

    return $transaction->id();
  }

}
