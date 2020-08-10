<?php

namespace Drupal\girchi_paypal\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class GirchiPaypalCommands extends DrushCommands {

  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * GirchiPaypalCommands constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {

    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Command to set all donations status to "TBC".
   *
   * @command girchi_paypal:set-donation-source
   * @aliases set-donation-source
   */
  public function setDonationSource() {
    $donation_storage = $this->entityTypeManager->getStorage('donation');
    $donations = $donation_storage->loadByProperties(['status' => 'OK']);
    foreach ($donations as $donation) {
      if ($donation->field_source->value == NULL) {
        $donation->set('field_source', 'tbc');
        $donation->save();
      }
    }
  }

}
