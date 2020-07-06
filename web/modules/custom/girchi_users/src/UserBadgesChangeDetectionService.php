<?php

namespace Drupal\girchi_users;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\girchi_notifications\Constants\NotificationConstants;
use Drupal\girchi_notifications\GetBadgeInfo;
use Drupal\girchi_notifications\NotifyUserService;
use Drupal\girchi_users\Constants\BadgeConstants;

/**
 * Class UserBadgesChangeDetectionService.
 */
class UserBadgesChangeDetectionService {
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
   * GetBadgeInfo.
   *
   * @var \Drupal\girchi_notifications\GetBadgeInfo
   */
  protected $getBadgeInfoService;

  /**
   * NotifyUserService.
   *
   * @var \Drupal\girchi_notifications\NotifyUserService
   */
  protected $notifyUser;

  /**
   * CreateGedTransaction constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   EntityTypeManager.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerChannelFactory
   *   Logger factory.
   * @param \Drupal\Component\Serialization\Json $json
   *   Json.
   * @param \Drupal\girchi_notifications\GetBadgeInfo $getBadgeInfo
   *   GetBadgeInfo.
   * @param \Drupal\girchi_notifications\NotifyUserService $notifyUserService
   *   NotifyUserService.
   */
  public function __construct(EntityTypeManager $entityTypeManager,
                              LoggerChannelFactory $loggerChannelFactory,
                              Json $json,
                              GetBadgeInfo $getBadgeInfo,
                              NotifyUserService $notifyUserService) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerFactory = $loggerChannelFactory->get('girchi_users');
    $this->json = $json;
    $this->getBadgeInfoService = $getBadgeInfo;
    $this->notifyUser = $notifyUserService;
  }

  /**
   * UserBadgesChangeDetection.
   *
   * @param \Drupal\Core\Entity\EntityInterface $user
   *   User.
   */
  public function userBadgesChangeDetection(EntityInterface $user) {
    try {
      $appearance_array = [
        'visibility' => TRUE,
        'selected' => FALSE,
        'approved' => TRUE,
        'status_message' => '',
        'earned_badge' => TRUE,
      ];
      $earned_badge = FALSE;
      $lost_badge = FALSE;
      $badge_name = '';
      $value = '';
      $encoded_value = $this->json->encode($appearance_array);

      if ($user->isNew()) {
        $earned_badge = TRUE;
        $badge_name = BadgeConstants::PORTAL_MEMBER;
        $value = $encoded_value;
      }
      elseif ($user->get('field_politician')->value == TRUE && $user->original->get('field_politician')->value == FALSE) {
        $earned_badge = TRUE;
        $badge_name = BadgeConstants::POLITICIAN;
        $value = $encoded_value;
      }
      elseif ($user->get('field_politician')->value == FALSE && $user->original->get('field_politician')->value == TRUE) {
        $lost_badge = TRUE;
        $badge_name = BadgeConstants::POLITICIAN;
        $value = '';
      }

      if ($earned_badge == TRUE || $lost_badge == TRUE) {
        $term = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['name' => $badge_name]);

        if ($term) {
          $tid = reset($term)->id();

          // Set parameters for NotifyUserService.
          $badge_info = $this->getBadgeInfoService->getBadgeInfo($tid);
          if ($lost_badge == TRUE) {
            $text = "თქვენ ჩამოგერთვათ ბეჯი - ${badge_info['badge_name']}.";
            $text_en = "You are deprived the badge - ${badge_info['badge_name_en']}.";
          }
          else {
            $text = "თქვენ მოგენიჭათ ბეჯი - ${badge_info['badge_name']}.";
            $text_en = "You have acquired the badge - ${badge_info['badge_name_en']}.";
          }
          $notification_type = NotificationConstants::USER_BADGE;
          $notification_type_en = NotificationConstants::USER_BADGE_EN;
          $badge_info['image'] = $badge_info['logo_svg'][$badge_name];

          /** @var \Drupal\Core\Field\FieldItemList $user_badges */
          $user_badges = $user->get('field_badges');
          if (!$user_badges->isEmpty()) {
            $user_badges_new = clone $user_badges;
            /** @var \Drupal\Core\Field\FieldItemList $badge_exists */
            $badge_exists = $user_badges_new->filter(static function ($user_badges) use ($tid) {
              return $tid == $user_badges->target_id;
            });

            if ($badge_exists->isEmpty()) {
              $user_badges->appendItem([
                'target_id' => $tid,
                'value' => $value,
              ]);
              // Notify user.
              $this->notifyUser->notifyUser($user->id(), $badge_info, $notification_type, $notification_type_en, $text, $text_en);
            }
            else {
              foreach ($user_badges as $user_badge) {
                if ($user_badge->target_id == $tid) {
                  $user_badge->set('value', $value);
                  // Notify user.
                  $this->notifyUser->notifyUser($user->id(), $badge_info, $notification_type, $notification_type_en, $text, $text_en);
                }
              }
            }
          }
          elseif ($user_badges->isEmpty()) {
            $user_badges->appendItem([
              'target_id' => $tid,
              'value' => $value,
            ]);
            // Notify user.
            $this->notifyUser->notifyUser($user->id(), $badge_info, $notification_type, $notification_type_en, $text, $text_en);
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

  }

  /**
   * AddDonationBadge.
   *
   * @param int $user_id
   *   User.
   * @param bool $single_donation
   *   Single donation.
   */
  public function addDonationBadge($user_id, $single_donation) {
    try {
      if ($user_id != 0) {
        $appearance_array = [
          'visibility' => TRUE,
          'selected' => FALSE,
          'approved' => TRUE,
          'status_message' => '',
          'earned_badge' => TRUE,
        ];

        $badge_name = '';
        $value = $this->json->encode($appearance_array);

        if ($single_donation == TRUE) {
          $badge_name = BadgeConstants::SINGLE_CONTRIBUTOR;
        }
        elseif ($single_donation == FALSE) {
          $badge_name = BadgeConstants::REGULAR_CONTRIBUTOR;
        }

        $term = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['name' => $badge_name]);
        $tid = reset($term)->id();
        $user = $this->entityTypeManager->getStorage('user')->load($user_id);
        /** @var \Drupal\Core\Field\FieldItemList $user_badges */
        $user_badges = $user->get('field_badges');
        if (!$user_badges->isEmpty()) {
          // Set parameters for NotifyUserService.
          $badge_info = $this->getBadgeInfoService->getBadgeInfo($tid);
          $text = "თქვენ მოგენიჭათ ბეჯი - ${badge_info['badge_name']}.";
          $text_en = "You have acquired the badge - ${badge_info['badge_name_en']}.";
          $notification_type = NotificationConstants::USER_BADGE;
          $notification_type_en = NotificationConstants::USER_BADGE_EN;
          $badge_info['image'] = $badge_info['logo_svg'][$badge_name];

          $user_badges_new = clone $user_badges;
          /** @var \Drupal\Core\Field\FieldItemList $badge_exists */
          $badge_exists = $user_badges_new->filter(static function ($user_badges) use ($tid) {
            return $tid == $user_badges->target_id;
          });
          if ($badge_exists->isEmpty()) {
            $user_badges->appendItem([
              'target_id' => $tid,
              'value' => $value,
            ]);
            $user->save();
            // Notify user.
            $this->notifyUser->notifyUser($user_id, $badge_info, $notification_type, $notification_type_en, $text, $text_en);
          }
          else {
            foreach ($user_badges as $user_badge) {
              if ($user_badge->target_id == $tid && $user_badge->value == '') {
                $user_badge->set('value', $value);
                $user->save();
                // Notify user.
                $this->notifyUser->notifyUser($user_id, $badge_info, $notification_type, $notification_type_en, $text, $text_en);
              }

            }
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

  /**
   * DeleteRegDonationBadge.
   *
   * @param int $uid
   *   Uid.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteRegDonationBadge($uid) {
    try {
      $user = $this->entityTypeManager->getStorage('user')->load($uid);
      $badge_name = BadgeConstants::REGULAR_CONTRIBUTOR;
      $regular_donation_storage = $this->entityTypeManager->getStorage('regular_donation');
      $regular_donation = $regular_donation_storage->getQuery()
        ->condition('user_id', $user->id(), '=')
        ->condition('status', 'ACTIVE', '=')
        ->execute();
      if (empty($regular_donation)) {
        $term = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['name' => $badge_name]);
        $tid = reset($term)->id();
        /** @var \Drupal\Core\Field\FieldItemList $user_badges */
        $user_badges = $user->get('field_badges');
        if (!$user_badges->isEmpty()) {
          foreach ($user_badges as $user_badge) {
            if ($user_badge->target_id == $tid) {
              // Set parameters for NotifyUserService.
              $badge_info = $this->getBadgeInfoService->getBadgeInfo($tid);
              $text = "თქვენ ჩამოგერთვათ ბეჯი - ${badge_info['badge_name']}.";
              $text_en = "You are deprived the badge - ${badge_info['badge_name_en']}.";
              $notification_type = NotificationConstants::USER_BADGE;
              $notification_type_en = NotificationConstants::USER_BADGE_EN;
              $badge_info['image'] = $badge_info['logo_svg'][$badge_name];

              // Save badge value.
              $user_badge->set('value', '');
              $user->save();
              // Notify user.
              $this->notifyUser->notifyUser($uid, $badge_info, $notification_type, $notification_type_en, $text, $text_en);
            }
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
  }

}
