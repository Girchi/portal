<?php

namespace Drupal\girchi_notifications;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\girchi_notifications\Constants\NotificationConstants;

/**
 * Class NotifyAdminService.
 */
class NotifyAdminService {
  /**
   * Entity type Manager.
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
   * GetUserInfo.
   *
   * @var \Drupal\girchi_notifications\GetUserInfoService
   */
  protected $getUserInfo;

  /**
   * GetBadgeInfoService.
   *
   * @var \Drupal\girchi_notifications\GetBadgeInfo
   */
  protected $getBadgeInfo;

  /**
   * NotifyUserService.
   *
   * @var \Drupal\girchi_notifications\NotifyUserService
   */
  protected $notifyUser;

  /**
   * ConfigFactory.
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a new GetUserInfoService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   Logger messages.
   * @param \Drupal\girchi_notifications\GetUserInfoService $getUserInfoService
   *   GetUserInfo.
   * @param \Drupal\girchi_notifications\GetBadgeInfo $getBadgeInfo
   *   GetBadgeInfo.
   * @param \Drupal\girchi_notifications\NotifyUserService $notifyUserService
   *   NotifyUserService.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   ConfigFactory.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager,
                              LoggerChannelFactory $loggerFactory,
                              GetUserInfoService $getUserInfoService,
                              GetBadgeInfo $getBadgeInfo,
                              NotifyUserService $notifyUserService,
                              ConfigFactory $configFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerFactory = $loggerFactory->get('girchi_notifications');
    $this->getUserInfo = $getUserInfoService;
    $this->getBadgeInfo = $getBadgeInfo;
    $this->notifyUser = $notifyUserService;
    $this->configFactory = $configFactory;

  }

  /**
   * @param int $invoker_id
   *   Invoker_id.
   * @param int $badge_id
   *   Badge_id.
   */
  public function badgeRequest($invoker_id, $badge_id) {
    $invokerService = $this->getUserInfo->getUserInfo($invoker_id);
    $badge_info = $this->getBadgeInfo->getBadgeInfo($badge_id);
    $invoker = array_merge($invokerService, $badge_info);
    $text = "${invokerService['full_name']}-მ გამოგიგზავნათ მოთხოვნა ბეჯზე -${badge_info['badge_name']}";
    $text_en = "${invokerService['full_name']} has sent you a request for badge - ${badge_info['badge_name']}";
    $notification_type = NotificationConstants::BADGE;
    $notification_type_en = NotificationConstants::BADGE_EN;
    $user = $this->configFactory->get('om_site_settings.site_settings')->get('default_receiver');
    //Notify user.
    $this->notifyUser->notifyUser($user, $invoker,$notification_type, $notification_type_en, $text, $text_en);

  }

}
