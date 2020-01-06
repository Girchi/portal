<?php

namespace Drupal\girchi_donations\Event;

use Drupal\girchi_donations\Entity\Donation;
use Symfony\Component\EventDispatcher\Event;

/**
 * Donation events.
 */
class DonationEvents extends Event {


  /**
   * Donation.
   *
   * @var \Drupal\girchi_donations\Entity\Donation
   */
  protected $donation;

  /**
   * DonationEvents constructor.
   *
   * @param \Drupal\girchi_donations\Entity\Donation $donation
   *   Donation.
   */
  public function __construct(Donation $donation) {
    $this->donation = $donation;
  }

  /**
   * Get user from donation.
   *
   * @return mixed
   *   mixed.
   */
  public function getUser() {

    return $this->donation->getUser();
  }

  /**
   * Get Donation.
   *
   * @return \Drupal\girchi_donations\Entity\Donation
   *   Donation.
   */
  public function getDonation() {
    return $this->donation;
  }

}
