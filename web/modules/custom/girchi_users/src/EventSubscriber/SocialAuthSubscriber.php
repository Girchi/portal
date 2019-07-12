<?php

namespace Drupal\girchi_users\EventSubscriber;

use Drupal\social_auth\Event\SocialAuthEvents;
use Drupal\social_auth\Event\SocialAuthUserEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Social Auth Subsciriber.
 */
class SocialAuthSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[SocialAuthEvents::USER_LOGIN] = ['onUserLogin'];

    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function onUserLogin(SocialAuthUserEvent $event) {
    $user = $event->getUser();
    if ($user->get('field_social_auth_password')->getValue()) {
      $password_set = $user->get('field_social_auth_password')->getValue()[0]['value'];
    }
    else {
      $password_set = FALSE;
    }

    if (!$password_set) {
      $response = new RedirectResponse("/createpassword");
      $response->send();
      return;
    }

  }

}
