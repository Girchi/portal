<?php

namespace Drupal\girchi_utils;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\Entity\User;

/**
 * Class GedRaitingService.
 */
class GedRaitingService {

  /**
   * EntityTypeManagerInterface definition.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new PartyListCalculatorService object.
   *
   * @param EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager)
  {
    $this->entityTypeManager = $entity_type_manager;
  }

  public function calculateRankRating()
  {
    $users = null;
    try {
      $user_storage = $this->entityTypeManager->getStorage('user');
      $user_ids = $user_storage->getQuery()
        ->condition('field_ged', '0', '>=')
        ->sort('field_ged', 'DESC')
        ->sort('field_last_name', 'ASC')
        ->execute();
      /** @var User $users */
      $users = $user_storage->loadMultiple($user_ids);
      $ged_rank = 1;
      foreach ($users as $user){
       $user->set('field_rank', $ged_rank);
        try {
          $user->save();
          $ged_rank++;
        } catch (EntityStorageException $e) {
        }
      }
    } catch (InvalidPluginDefinitionException $e) {
    } catch (PluginNotFoundException $e) {
    }
  }

}
