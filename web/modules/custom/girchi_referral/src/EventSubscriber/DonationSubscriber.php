<?php

namespace Drupal\girchi_referral\EventSubscriber;

use Drupal\girchi_donations\Event\DonationEventsConstants;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DonationSubscriber implements EventSubscriberInterface
{

  /**
   * Returns an array of event names this subscriber wants to listen to.
   *
   * @return array The event names to listen to
   */
  public static function getSubscribedEvents() {
    $events[DonationEventsConstants::DONATION_SUCCESS] = ['onDonationCreation'];

    return $events;
  }

  /**
   * @param \Drupal\girchi_donations\Event\DonationEventsConstants $event
   */
  public function onDonationCreation(DonationEventsConstants $event) {
    dump($event);
  }

}
