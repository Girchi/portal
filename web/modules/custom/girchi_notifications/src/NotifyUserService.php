<?php

namespace Drupal\girchi_notifications;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\girchi_notifications\Constants\NotificationConstants;
use GuzzleHttp\Client;
use Symfony\Component\Dotenv\Dotenv;
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
   * HttpClient.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Constructs a new SummaryGedCalculationService object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   Logger messages.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Request.
   * @param \GuzzleHttp\Client $httpClient
   *   HttpClient.
   */
  public function __construct(LoggerChannelFactory $loggerFactory, RequestStack $request, Client $httpClient) {
    $this->loggerFactory = $loggerFactory->get('girchi_notifications');
    $this->request = $request;
    $this->httpClient = $httpClient;
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
   * @param string $type_en
   *   Type.
   * @param string $text
   *   Text.
   * @param string $text_en
   *   Text.
   *
   * @return bool|void
   *   Response.
   */
  public function notifyUser($user_id, array $invoker, $type, $type_en, $text, $text_en) {
    try {
      $url = $this->request->getCurrentRequest()->getSchemeAndHttpHost();
      $link = '';

      if ($type_en == NotificationConstants::DONATION_EN) {
        $link = $url . '/user/' . $invoker['uid'];
      }
      elseif ($type_en == NotificationConstants::REFERRAL_EN) {
        $link = $url . '/user/' . $user_id . '?show_referral_modal=true';
      }
      elseif ($type_en == NotificationConstants::PARTY_LIST_EN) {
        $link = $url . '/user/' . $invoker['uid'] . '?show_partyList_modal=true';
      }
      elseif ($type_en == NotificationConstants::BADGE_EN) {
        $link = $url . '/admin/custom-badges?tid=' . $invoker['badge_id'] . '&uid=' . $invoker['uid'];
      }
      elseif ($type_en == NotificationConstants::USER_BADGE_EN || $type_en == NotificationConstants::TESLA) {
        $link = $url . '/user/' . $user_id;
      }

      $notification = [
        'title' => $type,
        'title_en' => $type_en,
        'desc' => $text,
        'desc_en' => $text_en,
        'type' => $type_en,
        'user' => $user_id,
        'link' => $link,
        'photoUrl' => $invoker['image'],
      ];
      $encoded_notification = json::encode($notification);

      $options = [
        'method' => 'POST',
        'body' => $encoded_notification,
        'headers' => ['Content-Type' => 'application/json'],
      ];

      $dotEnv = new Dotenv();
      $dotEnv->load('modules/custom/girchi_notifications/Credentials/.cred.env');
      $host = $_ENV['HOST'];
      $result = $this->httpClient->post($host, $options);

      if ($result->getStatusCode() == 200 || $result->getStatusCode() == 201) {
        return TRUE;
      }
      return FALSE;

    }
    catch (\Exception $e) {
      $this->loggerFactory->error($e->getMessage());

    }

  }

}
