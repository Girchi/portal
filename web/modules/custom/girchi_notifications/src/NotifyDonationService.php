<?php

namespace Drupal\girchi_notifications;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\girchi_donations\Entity\Donation;
use Drupal\girchi_notifications\Constants\NotificationConstants;

/**
 * Class NotifyDonationService.
 */
class NotifyDonationService {
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
   * NotifyUserService.
   *
   * @var \Drupal\girchi_notifications\NotifyUserService
   */
  protected $notifyUserService;

  /**
   * GetUserInfoService;.
   *
   * @var \Drupal\girchi_notifications\GetUserInfoService
   */
  protected $getUserInfoService;

  /**
   * Constructs a new SummaryGedCalculationService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   Logger messages.
   * @param \Drupal\girchi_notifications\NotifyUserService $notifyUserService
   *   NotifyUser service.
   * @param \Drupal\girchi_notifications\GetUserInfoService $getUserInfoService
   *   GetUserInfo service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager,
                              LoggerChannelFactory $loggerFactory,
                              NotifyUserService $notifyUserService,
                              GetUserInfoService $getUserInfoService) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerFactory = $loggerFactory->get('girchi_notifications');
    $this->notifyUserService = $notifyUserService;
    $this->getUserInfoService = $getUserInfoService;
  }

  /**
   * Function to get assigned user from Donation Aim.
   *
   * @param \Drupal\girchi_donations\Entity\Donation $donation
   *   Donation.
   */
  public function notifyDonation(Donation $donation) {
    try {
      $type = !empty($donation->getAim()) ? 1 : 2;
      $user_id = $donation->getUser()->id();
      // $invoker is person who caused notification.
      $invoker = $this->getUserInfoService->getUserInfo($user_id);
      $amount = $donation->getAmount();
      $notification_type = NotificationConstants::DONATION;
      $notification_type_en = NotificationConstants::DONATION_EN;
      if ($type == 1) {
        $donation_aim = $donation->getAim()->id();
        $taxonomy_storage = $this->entityTypeManager->getStorage('taxonomy_term')->load($donation_aim);
        if (!empty($taxonomy_storage)) {
          $aim_name = $taxonomy_storage->get('name')->value;
          foreach ($taxonomy_storage->get('field_user') as $assigned_user) {
            if ($user_id == 0) {
              $text = "ანონიმურმა მომხმარებელმა დააფინანსა ${aim_name} ${amount} ლარით.";
              $text_en = "Anonymous user has donated ${amount} GEL to ${aim_name}.";
            }
            else {
              $text = "${invoker['full_name']}-მ დააფინანსა ${aim_name} ${amount} ლარით.";
              $text_en = "${invoker['full_name']} donated ${amount} GEL to ${aim_name}.";
            }
            $this->notifyUserService->notifyUser($assigned_user->target_id, $invoker, $notification_type, $notification_type_en, $text, $text_en);
          }
        }
      }
      else {
        $politician_id = $donation->getPolitician()->id();
        if ($user_id == 0) {
          $text = "ანონიმურმა მომხმარებელმა დაგაფინანსათ ${amount} ლარით.";
          $text_en = "Anonymous user has donated you ${amount} GEL.";
        }
        else {
          $text = "${invoker['full_name']}-მ  დაგაფინანსათ ${amount} ლარით.";
          $text_en = "${invoker['full_name']} donated you ${amount} GEL.";
        }
        $this->notifyUserService->notifyUser($politician_id, $invoker, $notification_type, $notification_type_en, $text, $text_en);
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
