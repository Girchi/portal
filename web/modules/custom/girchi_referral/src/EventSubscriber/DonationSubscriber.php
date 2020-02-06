<?php

namespace Drupal\girchi_referral\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\girchi_donations\Event\DonationEvents;
use Drupal\girchi_donations\Event\DonationEventsConstants;
use Drupal\girchi_referral\CreateReferralTransactionService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Donation subscriber.
 */
class DonationSubscriber implements EventSubscriberInterface {
  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
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
   *   Referral transaction service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(CreateReferralTransactionService $referralTransactionService, EntityTypeManagerInterface $entityTypeManager) {
    $this->referralTransactionService = $referralTransactionService;
    $this->entityTypeManager = $entityTypeManager;
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
   *   Event.
   */
  public function onDonationCreation(DonationEvents $event) {
    $user = $event->getUser();
    $donation = $event->getDonation();
    $referral_id = $user->get('field_referral')->target_id ?? '';
    $politician_id = $donation->get('politician_id')->target_id ?? '';

    // If politician and user referral is the same person
    // referral transaction should not be created.
    if (!empty($referral_id) && $referral_id != $politician_id) {
      $this->referralTransactionService->createReferralTransaction($user, $referral_id, $event->getDonation());
      $this->referralTransactionService->countReferralsMoney($referral_id);
    }

  }

}
