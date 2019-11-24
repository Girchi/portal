<?php

namespace Drupal\girchi_donations\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Donation entities.
 */
class DonationViewsData extends EntityViewsData {

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
