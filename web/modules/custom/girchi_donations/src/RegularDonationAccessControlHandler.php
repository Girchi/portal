<?php

namespace Drupal\girchi_donations;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Regular donation entity.
 *
 * @see \Drupal\girchi_donations\Entity\RegularDonation.
 */
class RegularDonationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\girchi_donations\Entity\RegularDonationInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view regular donation entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit regular donation entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete regular donation entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add regular donation entities');
  }

}
