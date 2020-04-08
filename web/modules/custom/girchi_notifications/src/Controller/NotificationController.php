<?php

namespace Drupal\girchi_notifications\Controller;

/**
 * Class NotificationController.
 */
class NotificationController {

  /**
   * Notifications.
   */
  public function notifications() {
    return [
      '#type' => 'markup',
      '#theme' => 'girchi_notifications',
    ];

  }

}
