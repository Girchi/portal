<?php

namespace Drupal\girchi_notifications;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Logger\LoggerChannelFactory;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class NotifyUserService.
 */
class NotifyUserService {
  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;


  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * Constructs a new SummaryGedCalculationService object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   Logger messages.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Request.
   */
  public function __construct(LoggerChannelFactory $loggerFactory, RequestStack $request) {
    $this->loggerFactory = $loggerFactory->get('girchi_notifications');
    $this->request = $request;
  }

  /**
   * Notify user.
   *
   * @param int $user_id
   *   User id.
   * @param array $invoker
   *   Invoker is person who caused notification.
   * @param string $type
   *   Type.
   * @param string $text
   *   Text.
   */
  public function notifyUser($user_id, array $invoker, $type, $text) {
    $host = $this->request->getCurrentRequest()->getHost();
    $link = '';

    if ($type == 'donation') {
      $link = $host . '/user/' . $invoker['uid'];
    }
    elseif ($type == 'referral') {
      $link = $host . '/user/' . $user_id . '?show_referral_modal=true';
    }
    elseif ($type == 'party_list') {
      $link = $host . '/user/' . $invoker['uid'] . '?show_partyList_modal=true';
    }

    $notification = [
      'text' => $text,
      'user' => $user_id,
      'link' => $link,
      'photoUrl' => $invoker['image'],
      'type' => $type,
    ];
    $encoded_notification = json::encode($notification);

    $options = [
      'method' => 'POST',
      'data' => $encoded_notification,
      'headers' => ['Content-Type' => 'application/json'],
    ];

    // TODO::Dependency injection!!
    $result = \Drupal::httpClient()->post('notifications.girchi.docker.localhost/notifications/', $options);

    if ($result->getStatusCode() == 200) {
      return TRUE;
    }
    else {
      return $this->loggerFactory->error($result->getStatusCode());
    }

  }

}
