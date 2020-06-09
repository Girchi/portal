<?php

namespace Drupal\girchi_users;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class UserApprovedBadgesService.
 */
class UserApprovedBadgesService {
  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Json.
   *
   * @var \Drupal\Component\Serialization\Json
   */
  protected $json;

  /**
   * CreateGedTransaction constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   EntityTypeManager.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerChannelFactory
   *   Logger factory.
   * @param \Drupal\Component\Serialization\Json $json
   */
  public function __construct(EntityTypeManager $entityTypeManager, LoggerChannelFactory $loggerChannelFactory, Json $json) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerFactory = $loggerChannelFactory->get('girchi_users');
    $this->json = $json;
  }

  /**
   * ApprovedBadges.
   */
  public function approvedBadges($user, $tid) {
    try {
      /** @var \Drupal\Core\Field\FieldItemList $approved_badges */
      $approved_badges = $user->get('field_approved_badges');
      if (!$approved_badges->isEmpty()) {
        $user_badges_new = clone $approved_badges;
        /** @var \Drupal\Core\Field\FieldItemList $supporter_exists */
        $badge_exists = $user_badges_new->filter(static function ($approved_badges) use ($tid) {
          return $tid == $approved_badges->target_id;
        });
        if ($badge_exists->isEmpty()) {
          $approved_badges->appendItem($tid);
        }
      }
      else {
        $approved_badges->appendItem($tid);
      }

    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->error($e->getMessage());
    }
    catch (EntityStorageException $e) {
      $this->loggerFactory->error($e->getMessage());
    }

  }

  /**
   * RemoveBadges.
   */
  public function removeBadge($user, $tid) {
    try {
      /** @var \Drupal\Core\Field\FieldItemList $approved_badges */
      $approved_badges = $user->get('field_approved_badges');
      if (!$approved_badges->isEmpty()) {
        $array_column = array_column($approved_badges->getValue(), 'target_id');
        if ($array_column) {
          $key = array_search($tid, $array_column);
          if ($key !== FALSE) {
            $approved_badges->removeItem($key);
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
    catch (EntityStorageException $e) {
      $this->loggerFactory->error($e->getMessage());
    }

  }

}
