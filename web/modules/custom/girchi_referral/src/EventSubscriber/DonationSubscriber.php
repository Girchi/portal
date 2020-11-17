<?php

namespace Drupal\girchi_referral\EventSubscriber;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
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
   * EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * DonationSubscriber constructon.
   *
   * @param \Drupal\girchi_referral\CreateReferralTransactionService $referralTransactionService
   *   Referral transaction service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger Factory.
   */
  public function __construct(CreateReferralTransactionService $referralTransactionService,
                              EntityTypeManagerInterface $entityTypeManager,
                              LoggerChannelFactoryInterface $loggerFactory) {
    $this->referralTransactionService = $referralTransactionService;
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerFactory = $loggerFactory->get('girchi_referral');
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
      try {
        $referral_user = $this->entityTypeManager->getStorage('user')->load($referral_id);
        // Check if referral user exists and create referral transaction.
        if (!empty($referral_user)) {
          $this->referralTransactionService->createReferralTransaction($user, $referral_id, $event->getDonation());
          $this->referralTransactionService->countReferralsMoney($referral_id);
        }
      }
      catch (InvalidPluginDefinitionException $e) {
        $this->loggerFactory->error($e->getMessage());
      }
      catch (PluginNotFoundException $e) {
        $this->loggerFactory->error($e->getMessage());
      }

    }

  }

}
