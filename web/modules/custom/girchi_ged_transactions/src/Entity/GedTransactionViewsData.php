<?php

namespace Drupal\girchi_ged_transactions\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for GED transaction entities.
 */
class GedTransactionViewsData extends EntityViewsData {

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
