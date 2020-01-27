<?php

namespace Drupal\girchi_referral;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class GetUserReferralsService.
 */
class GetUserReferralsService {

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
   * GetUserReferrals.
   */
  public function getUserReferrals($uid) {
    // Get referrals.
    $user_storage = $this->entityTypeManager->getStorage('user');
    $referralsId = $user_storage->getQuery()
      ->condition('field_referral', $uid)
      ->condition('field_first_name', NULL, 'IS NOT NULL')
      ->condition('field_last_name', NULL, 'IS NOT NULL')
      ->sort('field_ged', 'DESC')
      ->execute();

    // Count number of referrals.
    $countReferrals = count($referralsId);

    $referralsArray = $user_storage->loadMultiple($referralsId);

    $resultArr = [
      'referralCount' => $countReferrals,
      'referralUsers' => $referralsArray,
    ];

    return $resultArr;

  }

}
