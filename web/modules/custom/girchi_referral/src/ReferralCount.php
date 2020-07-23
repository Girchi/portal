<?php

namespace Drupal\girchi_referral;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class ReferralCount.
 *
 * @package Drupal\girchi_referral
 */
class ReferralCount {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * GetUserReferralsService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *
   *   Entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   loggerFactory.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactory $loggerFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerFactory = $loggerFactory->get('girchi_referrals');
  }

  /**
   * Function to dispatch events on user referral field.
   *
   * @param string $event
   *   Event to dispatch.
   * @param string $uid
   *   User id whom to dispatch event.
   */
  public function dispatch($event, $uid) {

    try {
      $user = $this->entityTypeManager->getStorage('user')->load($uid);
      if ($user) {
        $referralCount = $user->get('field_referral_count')->value;
        switch ($event) {
          case 'INCREMENT':
            $user->set('field_referral_count', ++$referralCount);
            break;

          case 'DECREMENT':
            $user->set('field_referral_count', --$referralCount);
            break;

          default:
            break;
        }
        $user->save();
      }
      else {
        $this->loggerFactory->error("Referral user was deleted from portal");
      }

    }
    catch (\Exception $exception) {
      $this->loggerFactory->error($exception);
    }

  }

}
