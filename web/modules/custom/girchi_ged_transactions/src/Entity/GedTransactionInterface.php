<?php

namespace Drupal\girchi_ged_transactions\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining GED transaction entities.
 *
 * @ingroup girchi_ged_transactions
 */
interface GedTransactionInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the GED transaction name.
   *
   * @return string
   *   Name of the GED transaction.
   */
  public function getName();

  /**
   * Sets the GED transaction name.
   *
   * @param string $name
   *   The GED transaction name.
   *
   * @return \Drupal\girchi_ged_transactions\Entity\GedTransactionInterface
   *   The called GED transaction entity.
   */
  public function setName($name);

  /**
   * Gets the GED transaction creation timestamp.
   *
   * @return int
   *   Creation timestamp of the GED transaction.
   */
  public function getCreatedTime();

  /**
   * Sets the GED transaction creation timestamp.
   *
   * @param int $timestamp
   *   The GED transaction creation timestamp.
   *
   * @return \Drupal\girchi_ged_transactions\Entity\GedTransactionInterface
   *   The called GED transaction entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the GED transaction published status indicator.
   *
   * Unpublished GED transaction are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the GED transaction is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a GED transaction.
   *
   * @param bool $published
   *   TRUE to set this GED transaction to published,
   *   FALSE to set it to unpublished.
   *
   * @return \Drupal\girchi_ged_transactions\Entity\GedTransactionInterface
   *   The called GED transaction entity.
   */
  public function setPublished($published);

}
