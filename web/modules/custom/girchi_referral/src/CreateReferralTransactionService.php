<?php


namespace Drupal\girchi_referral;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class CreateReferralTransactions
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
   * Function to create referral transaction
   */
  public function createReferralTransaction(){


  }



}
