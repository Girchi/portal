<?php

namespace Drupal\girchi_referral\Commands;

use Drupal\Core\Entity\EntityTypeManager;
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
class GirchiReferralCommands extends DrushCommands {

  /**
   * EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Construct.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   ET manager.
   */
  public function __construct(EntityTypeManager $entityTypeManager) {
    parent::__construct();
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Main command.
   *
   * @command girchi_referral:default-referral-date
   * @aliases default-referral-date
   */
  public function setReferralDate() {
    $user_storage = $this->entityTypeManager->getStorage('user');
    $uid = $user_storage->getQuery()
      ->condition('field_referral', NULL, 'IS NOT NULL')
      ->execute();
    $users = $user_storage->loadMultiple($uid);
    foreach ($users as $user) {
      $user->set('field_referral_date', date('Y-m-d', time()));
      $user->save();
    }

  }

}
