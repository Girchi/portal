<?php

namespace Drupal\girchi_donations;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Donation entity.
 *
 * @see \Drupal\girchi_donations\Entity\Donation.
 */
class DonationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\girchi_donations\Entity\DonationInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view published donation entities');
//
//      case 'update':
//        return AccessResult::allowedIfHasPermission($account, 'edit donation entities');

//      case 'delete':
//        return AccessResult::allowedIfHasPermission($account, 'delete donation entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add donation entities');
  }

}
