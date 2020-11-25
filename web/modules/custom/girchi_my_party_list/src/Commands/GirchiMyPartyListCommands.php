<?php

namespace Drupal\girchi_my_party_list\Commands;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
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
class GirchiMyPartyListCommands extends DrushCommands {

  /**
   * EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * LoggerFactory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Construct.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   ET manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger.
   */
  public function __construct(EntityTypeManager $entityTypeManager, LoggerChannelFactoryInterface $loggerFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * Main command.
   *
   * @command girchi_my_party_list:fix-party-list
   * @aliases fix-party-list
   */
  public function fixPartyList() {
    $userStorage = $this->entityTypeManager->getStorage('user');
    $userIds = $userStorage->getQuery()
      ->condition('field_my_party_list', NULL, 'IS NOT NULL')
      ->execute();

    $users = $userStorage->loadMultiple($userIds);
    foreach ($users as $user) {
      $party_list = $user->get('field_my_party_list');
      foreach ($party_list as $politician) {
        $politician = $userStorage->load($politician->target_id);
        if ($politician) {
          if ($politician->field_politician->value == 0) {
            try {
              $key = array_search($politician->id(), array_column($party_list, 'target_id'));
              $user->get('field_my_party_list')->removeItem((int) $key);
              $user->save();
            }
            catch (\Exception $e) {
              $this->loggerFactory->get('girchi_my_party_list')->error($e->getMessage());
              echo $e->getMessage();
            }
          }
        }
      }
    }

  }

}
