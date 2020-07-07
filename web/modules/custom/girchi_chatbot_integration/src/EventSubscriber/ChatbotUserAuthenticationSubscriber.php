<?php

namespace Drupal\girchi_chatbot_integration\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\girchi_chatbot_integration\Services\ChatbotIntegrationHelpers;
use Drupal\girchi_users\Event\UserLoginEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Class ChatbotUserAuthenticationSubscriber.
 */
class ChatbotUserAuthenticationSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\girchi_chatbot_integration\Services\ChatbotIntegrationHelpers
   */
  protected $chatbotHelpers;

  /**
   * Constructs a new ChatbotUserAuthenticationSubscriber object.
   */
  public function __construct(
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    ChatbotIntegrationHelpers $chatbotHelpers) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->chatbotHelpers = $chatbotHelpers;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[UserLoginEvent::USER_LOGIN] = 'onUserLogin';

    return $events;
  }

  /**
   * This method is called when the UserLoginEvent::USER_LOGIN is dispatched.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The dispatched event.
   */
  public function onUserLogin(Event $event) {
    if ($user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id())) {
      if ($new_code = $this->chatbotHelpers->generateUniqueCode($user)) {
        $user->set('field_bot_integration_code', $new_code);
        $user->save();
        $this->chatbotHelpers->logNewCode($user);
      }
    }
  }

}
