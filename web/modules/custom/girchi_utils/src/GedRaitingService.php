<?php

namespace Drupal\girchi_utils;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class GedRaitingService.
 */
class GedRaitingService {

  /**
   * EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new PartyListCalculatorService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *
   *   Entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * CalculateRankRating.
   */
  public function calculateRankRating() {
    $users = NULL;
    try {
      $user_storage = $this->entityTypeManager->getStorage('user');
      $user_ids = $user_storage->getQuery()
        ->condition('mail', NULL, 'IS NOT NULL')
        ->sort('field_ged', 'DESC')
        ->sort('field_last_name', 'ASC')
        ->execute();
      /** @var \Drupal\user\Entity\User $users */
      $users = $user_storage->loadMultiple($user_ids);
      $ged_rank = 1;
      foreach ($users as $user) {
        $user->set('field_rank', $ged_rank);
        try {
          $user->save();
          $ged_rank++;
        }
        catch (EntityStorageException $e) {
        }
      }
    }
    catch (InvalidPluginDefinitionException $e) {
    }
    catch (PluginNotFoundException $e) {
    }
  }

}
