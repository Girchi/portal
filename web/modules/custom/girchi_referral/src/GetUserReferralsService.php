<?php

namespace Drupal\girchi_referral;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\image\Entity\ImageStyle;

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

  /**
   * GetUserReferrals.
   */
  public function getUserReferralTree() {
    // Get referrals.
    $userStorage = $this->entityTypeManager->getStorage('user');
    $referralsId = $userStorage->getQuery()
      ->condition('field_referral', NULL, 'IS NOT NULL')
      ->condition('field_first_name', NULL, 'IS NOT NULL')
      ->condition('field_last_name', NULL, 'IS NOT NULL')
      ->sort('field_ged', 'DESC')
      ->execute();

    $resArray = [];

    $referralsArray = $userStorage->loadMultiple($referralsId);

    foreach ($referralsArray as $referral) {
      $referralId = $referral->get('field_referral')->target_id;
      if (isset($resArray[$referralId])) {
        $resArray[$referralId]++;
      }
      else {
        $resArray[$referralId] = 1;
      }

    }

    return $resArray;

  }

  /**
   * Get Users.
   *
   * @param string $userId
   *   user ID.
   *
   * @return array
   *   returns user info array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getReferralsWithInfo($userId) {
    $user_info = [];
    if (!empty($userId)) {
      $referrals = $this->getUserReferrals($userId);
      foreach ($referrals['referralUsers'] as $user) {
        $img_url = '';
        if (!empty($user->get('user_picture')[0])) {
          $img_id = $user->get('user_picture')[0]->getValue()['target_id'];
          $img_file = $this->entityTypeManager->getStorage('file')->load($img_id);
          $style = ImageStyle::load('party_member');
          $img_url = $style->buildUrl($img_file->getFileUri());
        }
        $first_name = $user->get('field_first_name')->value;
        $last_name = $user->get('field_last_name')->value;
        $user_info[] = [
          'img_url' => $img_url,
          'name' => implode(" ", [$first_name, $last_name]),
          'id' => $user->id(),
        ];

      }
    }
    return $user_info;
  }

}
