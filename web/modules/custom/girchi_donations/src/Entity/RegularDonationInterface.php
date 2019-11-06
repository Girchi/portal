<?php

namespace Drupal\girchi_donations\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Regular donation entities.
 *
 * @ingroup girchi_donations
 */
interface RegularDonationInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Regular donation creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Regular donation.
   */
  public function getCreatedTime();

  /**
   * Sets the Regular donation creation timestamp.
   *
   * @param int $timestamp
   *   The Regular donation creation timestamp.
   *
   * @return \Drupal\girchi_donations\Entity\RegularDonationInterface
   *   The called Regular donation entity.
   */
  public function setCreatedTime($timestamp);

}
