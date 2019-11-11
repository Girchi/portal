<?php

namespace Drupal\girchi_banking\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Credit card entities.
 *
 * @ingroup girchi_banking
 */
interface CreditCardInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Credit card name.
   *
   * @return string
   *   Name of the Credit card.
   */
  public function getName();

  /**
   * Sets the Credit card name.
   *
   * @param string $name
   *   The Credit card name.
   *
   * @return \Drupal\girchi_banking\Entity\CreditCardInterface
   *   The called Credit card entity.
   */
  public function setName($name);

  /**
   * Gets the Credit card creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Credit card.
   */
  public function getCreatedTime();

  /**
   * Sets the Credit card creation timestamp.
   *
   * @param int $timestamp
   *   The Credit card creation timestamp.
   *
   * @return \Drupal\girchi_banking\Entity\CreditCardInterface
   *   The called Credit card entity.
   */
  public function setCreatedTime($timestamp);

}
