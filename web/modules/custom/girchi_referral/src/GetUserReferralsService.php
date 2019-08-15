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
    // Count number of referrals.
    $user_storage = $this->entityTypeManager->getStorage('user');
    $countReferrals = $user_storage->getQuery()
      ->condition('field_referral', $uid)
      ->count()
      ->execute();

    // Get referrals.
    $referralsId = $user_storage->getQuery()
      ->condition('field_referral', $uid)
      ->sort('field_ged', 'DESC')
      ->execute();

    $referralsArray = $user_storage->loadMultiple($referralsId);

    $resultArr = [
      'referralCount' => $countReferrals,
      'referralUsers' => $referralsArray,
    ];

    return $resultArr;

  }

}
