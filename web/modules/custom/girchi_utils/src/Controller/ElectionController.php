<?php

namespace Drupal\girchi_utils\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 *
 */
class ElectionController extends ControllerBase {

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function election() {
    return [
      '#theme' => 'page_election_2020',
      '#type' => 'markup',
    ];
  }

}
