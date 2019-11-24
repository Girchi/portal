<?php

namespace Drupal\girchi_donations\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Donation entities.
 *
 * @ingroup girchi_donations
 */
interface DonationInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.
  /**
   * Gets the Donation creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Donation.
   */
  public function getCreatedTime();

  /**
   * Sets the Donation creation timestamp.
   *
   * @param int $timestamp
   *   The Donation creation timestamp.
   *
   * @return \Drupal\girchi_donations\Entity\DonationInterface
   *   The called Donation entity.
   */
  public function setCreatedTime($timestamp);


}
