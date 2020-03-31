<?php

namespace Drupal\girchi_my_party_list;

use Drupal\Core\Entity\EntityInterface;
use Drupal\girchi_notifications\Constants\NotificationConstants;
use Drupal\girchi_notifications\GetUserInfoService;
use Drupal\girchi_notifications\NotifyUserService;

/**
 * PartyListChangeDetectionService.
 */
class PartyListChangeDetectionService {
  /**
   * GetUserInfoService.
   *
   * @var \Drupal\girchi_notifications\GetUserInfoService
   */
  protected $getUserInfoService;

  /**
   * NotifyUserService.
   *
   * @var \Drupal\girchi_notifications\NotifyUserService
   */
  protected $notifyUserService;

  /**
   * Constructs a new PartyListChangeDetectionService object.
   *
   * @param \Drupal\girchi_notifications\GetUserInfoService $getUserInfoService
   *   GetUserInfoService.
   * @param \Drupal\girchi_notifications\NotifyUserService $notifyUserService
   *   NotifyUserService.
   */
  public function __construct(GetUserInfoService $getUserInfoService, NotifyUserService $notifyUserService) {
    $this->getUserInfoService = $getUserInfoService;
    $this->notifyUserService = $notifyUserService;
  }

  /**
   * PartyListChangeDetection.
   *
   * @param \Drupal\Core\Entity\EntityInterface $user
   *   User.
   */
  public function partyListChangeDetection(EntityInterface $user) {
    /** @var \Drupal\Core\Field\FieldItemList $supporters */
    $supporters = $user->get('field_my_party_list')->value ? $user->get('field_my_party_list') : NULL;
    $user_id = $user->id();
    $type = NotificationConstants::PARTY_LIST;
    $type_en = NotificationConstants::PARTY_LIST_EN;
    $getUserInfo = $this->getUserInfoService->getUserInfo($user_id);
    $original_supporter_id_array = [];
    if (!empty($user->original->get('field_my_party_list')->value)) {
      $original_supporters = $user->original->get('field_my_party_list');
      foreach ($original_supporters as $original_supporter) {
        $original_user_id = $original_supporter->target_id;
        $original_value = $original_supporter->value;
        $original_supporter_id_array[$original_user_id] = $original_value;

        // Notify if current state of party list is empty
        // and supporter was removed from last submit.
        if (empty($supporters)) {
          $supporter_id = $original_user_id;
          $text = "${getUserInfo['full_name']}-მ წაგშალათ პირადი პარტიული სიიდან.";
          $text_en = "${getUserInfo['full_name']} has removed you from private party list.";
          $this->notifyUserService->notifyUser($supporter_id, $getUserInfo, $type, $type_en, $text, $text_en);
        }

        // Notify if current state of party list wasn't empty
        // and supporter was removed from it.
        if (!empty($supporters)) {
          $supporters_new = clone $supporters;
          /** @var \Drupal\Core\Field\FieldItemList $supporter_exists */
          $supporter_exists = $supporters_new->filter(static function ($supporter) use ($original_supporter) {
            return $original_supporter->target_id == $supporter->target_id;
          });
          if ($supporter_exists->isEmpty()) {
            $text = "${getUserInfo['full_name']}-მ წაგშალათ პირადი პარტიული სიიდან.";
            $text_en = "${getUserInfo['full_name']} has removed you from private party list.";
            $this->notifyUserService->notifyUser($original_user_id, $getUserInfo, $type, $type_en, $text, $text_en);
          }
        }
      }
    }

    if (!empty($supporters)) {
      foreach ($supporters as $supporter) {
        $supporter_id = $supporter->target_id;
        $ged_percentage = $supporter->value;
        $user_ged_amount = $user->get('field_ged')->value ? $user->get('field_ged')->value : 0;
        $supporter_ged_amount = round($user_ged_amount * $ged_percentage / 100);
        $longFormattedGed = number_format($supporter_ged_amount, 0, ',', ' ');
        // If party list wasn't empty:
        if (!empty($user->original->get('field_my_party_list')->value)) {
          // Notify supporter that user decreased amount of ged percentage.
          if (array_key_exists($supporter_id, $original_supporter_id_array)) {
            $original_value = $original_supporter_id_array[$supporter_id];
            // Notify supporter that user decreased amount of ged percentage.
            if ($original_supporter_id_array[$supporter_id] > $ged_percentage) {
              $text = "${getUserInfo['full_name']}-მ შეგიმცირათ პოლიტიკური ჯედები ${original_value}%-დან ${ged_percentage}%-მდე - ${longFormattedGed}G.";
              $text_en = "${getUserInfo['full_name']} has decreased the amount of your political GED-s from ${original_value}% to ${ged_percentage}% - ${longFormattedGed}G.";
              $this->notifyUserService->notifyUser($supporter_id, $getUserInfo, $type, $type_en, $text, $text_en);
            }
            // Notify supporter that user increased amount of ged percentage.
            elseif ($original_supporter_id_array[$supporter_id] < $ged_percentage) {
              $text = "${getUserInfo['full_name']}-მ გაგიზარდათ პოლიტიკური ჯედები ${original_value}%-დან ${ged_percentage}%-მდე - ${longFormattedGed}G.";
              $text_en = "${getUserInfo['full_name']} has increased the amount of your political GED-s from ${original_value}% to ${ged_percentage}% - ${longFormattedGed}G.";
              $this->notifyUserService->notifyUser($supporter_id, $getUserInfo, $type, $type_en, $text, $text_en);
            }
          }
          // Notify if users party list wasn't empty
          // and supporter was added in it.
          elseif (!array_key_exists($supporter_id, $original_supporter_id_array)) {
            $text = "${getUserInfo['full_name']}-მ დაგამატათ პირად პარტიულ სიაში.";
            $text_en = "${getUserInfo['full_name']} has added you to the private party list.";
            $this->notifyUserService->notifyUser($supporter_id, $getUserInfo, $type, $type_en, $text, $text_en);
          }
        }
        // Notify if current state of party list was empty
        // and supporter was added in it.
        else {
          $text = "${getUserInfo['full_name']}-მ დაგამატათ პირად პარტიულ სიაში.";
          $text_en = "${getUserInfo['full_name']} has added you to the private party list.";
          $this->notifyUserService->notifyUser($supporter_id, $getUserInfo, $type, $type_en, $text, $text_en);
        }
      }
    }

  }

}
