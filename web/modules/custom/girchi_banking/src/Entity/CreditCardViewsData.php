<?php

namespace Drupal\girchi_banking\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Credit card entities.
 */
class CreditCardViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
