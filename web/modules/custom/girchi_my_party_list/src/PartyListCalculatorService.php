<?php

namespace Drupal\girchi_my_party_list;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class PartyListCalculatorService.
 */
class PartyListCalculatorService {

  /**
   * EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Constructs a new PartyListCalculatorService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *
   *   Entity Type Manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *
   *   Logger factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactory $loggerFactory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $loggerFactory->get('girchi_my_party_list');
  }

  /**
   * Calculate.
   */
  public function calculate() {
    try {
      // Array for full party list.
      $user_rating = [];

      /**
      * @var \Drupal\user\Entity\UserStorage $users
      */
      $user_storage = $this->entityTypeManager->getStorage('user');
      $user_ids = $user_storage->getQuery()
        ->condition('field_ged', '0', '>')
        ->condition('field_my_party_list', '0', '>')
        ->execute();
      $users = $user_storage->loadMultiple($user_ids);
      /**
      * @var \Drupal\user\Entity\User $user
      */
      if (!empty($users)) {
        foreach ($users as $user) {

          $user_party_list = $user->get('field_my_party_list')->getValue();
          $user_ged = (int) $user->get('field_ged')->getValue()[0]['value'];

          foreach ($user_party_list as $party_list_item) {
            $percentage = (int) $party_list_item['value'];
            $uid = $party_list_item['target_id'];
            $user_rating[$uid] = $user_ged * ($percentage / 100);
          };
        }
        arsort($user_rating);
        $rating_number = 1;
        foreach ($user_rating as $uid => $ged_amount) {
          /**
          * @var \Drupal\user\Entity\User $politician
          */
          $politician = $user_storage->load($uid);
          $politician->set('field_rating_in_party_list', $rating_number);
          $politician->set('field_political_ged', $ged_amount);
          try {
            $politician->save();
            $rating_number++;
          }
          catch (EntityStorageException $e) {
            $this->loggerFactory->error($e->getMessage());
          }
        }
      }
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->error($e->getMessage());
    }

  }

}
