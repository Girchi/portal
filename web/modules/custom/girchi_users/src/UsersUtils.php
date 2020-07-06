<?php

namespace Drupal\girchi_users;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Class UsersUtils.
 */
class UsersUtils {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Stores Entity manager object for users entity type.
   *
   * @var Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userManager;

  /**
   * Constructs a new UsersUtils object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->userManager = $entity_type_manager->getStorage('user');
  }

  /**
   * Checks if value for given field is already taken by other user.
   */
  public function fieldIsTaken($field_name, $field_value) {
    $users = $this->userManager->loadByProperties([$field_name => $field_value]);
    return !empty($users);
  }

}
