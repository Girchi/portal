<?php

namespace Drupal\girchi_chatbot_integration\Services;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\Entity\User;

/**
 * Class ChatbotIntegrationHelpers.
 */
class ChatbotIntegrationHelpers {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Entity\EntityStorageInterface definition.
   *
   * @var Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userManager;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Drupal\user\Entity\User Object.
   *
   * @var Drupal\user\Entity\User
   */
  private $currentUserObject;

  /**
   * Drupal\Core\Logger\LoggerChannel definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $loggerChannel;

  /**
   * Constructs a new ChatbotIntegrationHelpers object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $current_user,
    LoggerChannel $logger_channel) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->userManager = $entity_type_manager->getStorage('user');
    $this->currentUserObject = $this->userManager->load($this->currentUser->id());
    $this->loggerChannel = $logger_channel;
  }

  /**
   * Generates unique code for chatbot integration.
   */
  public function generateUniqueCode(EntityInterface $user) {
    $code = $user->field_bot_integration_code->getValue();
    if (!empty($code)) {
      return NULL;
    }

    $codeIsUnique = function ($code) {
      $loadedUsers = $this->userManager->loadByProperties([
        'field_bot_integration_code' => $code,
      ]);

      return empty($loadedUsers);
    };

    $new_code = sprintf('%07d', rand(0, 1000000));
    while (!$codeIsUnique($new_code)) {
      $new_code = sprintf('%07d', rand(0, 1000000));
    }
    return $new_code;
  }

  /**
   * Log new code generations.
   */
  public function logNewCode(User $user) {
    $this->loggerChannel->info('Chatbot integration code generated for @user', ['@user' => $user->getDisplayName()]);
  }

}
