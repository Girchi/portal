<?php

namespace Drupal\girchi_users\Commands;

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
class GirchiUsersCommands extends DrushCommands {
  /**
   * EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  public function __construct(EntityTypeManager $entityTypeManager) {
    parent::__construct();
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Main command.
   *
   * @command girchi_users:fix-field-tel
   * @aliases fix-field-tel
   */
  public function fixFieldTel() {
    $user_storage = $this->entityTypeManager->getStorage('user');
    $users_id = $user_storage->getQuery()
      ->condition('field_phone', NULL, 'IS NOT NULL')
      ->execute();
    $users = $user_storage->loadMultiple($users_id);
    foreach ($users as $user) {
      $old_value = $user->get('field_phone')->value;
      $user->get('field_tel')->value = NULL == $user->get('field_tel')->value ? $old_value : $user->get('field_tel')->value;
      $user->save();
    }
  }

  /**
   * Main command.
   *
   * @command girchi_users:publicity
   * @aliases publicity
   */
  public function publicity() {
    $users = $this->entityTypeManager->getStorage('user')->loadMultiple();
    foreach ($users as $user) {
      $user->set('field_publicity', TRUE);
      $user->save();
    }
  }

}
