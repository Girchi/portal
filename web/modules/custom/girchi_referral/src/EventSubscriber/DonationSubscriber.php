<?php

namespace Drupal\girchi_referral\EventSubscriber;

use Drupal\girchi_donations\Event\DonationEvents;
use Drupal\girchi_donations\Event\DonationEventsConstants;
use Drupal\girchi_referral\CreateReferralTransactionService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Donation subscriber.
 */
class DonationSubscriber implements EventSubscriberInterface {
  /**
   * Referral transaction service.
   *
   * @var \Drupal\girchi_referral\CreateReferralTransactionService
   */

  private $referralTransactionService;

  /**
   * DonationSubscriber constructon.
   *
   * @param \Drupal\girchi_referral\CreateReferralTransactionService $referralTransactionService
   *
   *   Referral transaction service.
   */
  public function __construct(CreateReferralTransactionService $referralTransactionService) {
    $this->referralTransactionService = $referralTransactionService;
  }

  /**
   * Get subscribed events.
   *
   * @return array
   *   The event names to listen to.
   */
  public static function getSubscribedEvents() {
    $events[DonationEventsConstants::DONATION_SUCCESS] = ['onDonationCreation'];

    return $events;
  }

  /**
   * On donation creation.
   *
   * @param \Drupal\girchi_donations\Event\DonationEvents $event
   *
   *   Event.
   */
  public function onDonationCreation(DonationEvents $event) {
    $user = $event->getUser();
    if ($referral_id = $user->get('field_referral')->target_id) {
      $this->referralTransactionService->createReferralTransaction($user, $referral_id, $event->getDonation());
      $this->referralTransactionService->countReferralsMoney($referral_id);
    }

  }

}
