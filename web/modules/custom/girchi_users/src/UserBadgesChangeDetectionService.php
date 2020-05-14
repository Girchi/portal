<?php

namespace Drupal\girchi_users;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactory;

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
   * CreateGedTransaction constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   EntityTypeManager.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerChannelFactory
   *   Logger factory.
   * @param \Drupal\Component\Serialization\Json $json
   *   Json.
   */
  public function __construct(EntityTypeManager $entityTypeManager, LoggerChannelFactory $loggerChannelFactory, Json $json) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerFactory = $loggerChannelFactory->get('girchi_users');
    $this->json = $json;
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
      ];
      $earned_badge = FALSE;
      $lost_badge = FALSE;
      $badge_name = '';
      $value = '';
      $encoded_value = $this->json->encode($appearance_array);

      if ($user->isNew()) {
        $earned_badge = TRUE;
        $badge_name = "პორტალის წევრი";
        $value = $encoded_value;
      }
      elseif ($user->get('field_politician')->value == TRUE && $user->original->get('field_politician')->value == FALSE) {
        $earned_badge = TRUE;
        $badge_name = "პოლიტიკოსი";
        $value = $encoded_value;

      }
      elseif ($user->get('field_politician')->value == FALSE && $user->original->get('field_politician')->value == TRUE) {
        $lost_badge = TRUE;
        $badge_name = "პოლიტიკოსი";
        $value = '';
      }

      if ($earned_badge == TRUE || $lost_badge == TRUE) {
        $term = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['name' => $badge_name]);
        $tid = reset($term)->id();

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
          }
          else {
            foreach ($user_badges as $user_badge) {
              if ($user_badge->target_id == $tid) {
                $user_badge->set('value', $value);
              }

            }
          }
        }
        elseif ($user_badges->isEmpty()) {
          $user_badges->appendItem([
            'target_id' => $tid,
            'value' => $value,
          ]);
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
    // TODO :: maybe notify user.
    try {
      if ($user_id != 0) {
        $appearance_array = [
          'visibility' => TRUE,
          'selected' => FALSE,
          'approved' => TRUE,
          'status_message' => '',
        ];

        $badge_name = '';
        $value = $this->json->encode($appearance_array);

        if ($single_donation == TRUE) {
          $badge_name = 'პარტნიორი - ერთჯერადი დამფინანსებელი';
        }
        elseif ($single_donation == FALSE) {
          $badge_name = 'პარტნიორი - მრავალჯერადი დამფინანსებელი';
        }

        $term = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['name' => $badge_name]);
        $tid = reset($term)->id();
        $user = $this->entityTypeManager->getStorage('user')->load($user_id);
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
            $user->save();
          }
          else {
            foreach ($user_badges as $user_badge) {
              if ($user_badge->target_id == $tid && $user_badge->value = '') {
                $user_badge->set('value', $value);
                $user->save();
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

  // TODO:: if regular donation was deleted
  //  public function donationBadgesChangeDetection(){
  //
  //  }
}
