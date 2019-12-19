<?php

namespace Drupal\girchi_referral;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\girchi_donations\Entity\Donation;

/**
 * Class CreateReferralTransactions.
 */
class CreateReferralTransactionService {
  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * GetUserReferralsService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *
   *   Entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Function to create referral transaction.
   */
  public function createReferralTransaction($user, $referral_id, Donation $donation) {
    $donation_entity = $donation->id();
    $donation_amount = $donation->getAmount();
    $ref_benefit = $donation_amount / 10;
    /** @var \Drupal\node\Entity\NodeStorage */
    $node_storage = $this->entityTypeManager->getStorage('node');
    $referral_transaction = $node_storage->create([
      'type' => 'referral_transaction',
      'field_user' => $user,
      'field_referral' => $referral_id,
      'field_donation' => $donation_entity,
      'field_amount_of_money' => $ref_benefit,
      'title' => 'Referral transaction',
    ]);
    $referral_transaction->save();

  }

  /**
   * CalculateAndUpdateTotalGeds.
   */
  public function countFeferralsMoney($uid) {
    $node_storage = $this->entityTypeManager->getStorage('node');
    $referral_transactions = $node_storage->loadByProperties(['field_referral' => $uid]);
    $sum_of_money = 0;
    foreach ($referral_transactions as $referral_transaction) {
      $amount_of_money = $referral_transaction->get('field_amount_of_money')->value;
      $sum_of_money = $sum_of_money + $amount_of_money;
    }

    $user = $this->entityTypeManager->getStorage('user')->load($uid);
    $user->set('field_referral_benefits', $sum_of_money);
    $user->save();
  }

}
