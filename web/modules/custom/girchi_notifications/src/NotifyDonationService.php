<?php

namespace Drupal\girchi_notifications;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;

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
   * Constructs a new SummaryGedCalculationService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   Logger messages.
   * @param \Drupal\girchi_notifications\NotifyUserService $notifyUserService
   *   Notify user service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager,
                              LoggerChannelFactory $loggerFactory,
                              NotifyUserService $notifyUserService) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerFactory = $loggerFactory->get('girchi_notifications');
    $this->notifyUserService = $notifyUserService;
  }

  /**
   * Function to get assigned user from Donation Aim.
   *
   * @param int $type
   *   Type.
   * @param array $invoker
   *   Invoker is person who caused notification.
   * @param int $amount
   *   Amount.
   * @param int $user_id
   *   User id.
   * @param string $donation_aim
   *   Donation aim.
   */
  public function notifyDonation($type, array $invoker, $amount, $user_id, $donation_aim) {
    try {
      $notification_type = 'დონაცია';
      $notification_type_en = 'donation';
      if ($type == 1) {
        $taxonomy_storage = $this->entityTypeManager->getStorage('taxonomy_term')->load($donation_aim);
        $aim_name = $taxonomy_storage->get('name')->value;
        foreach ($taxonomy_storage->get('field_user') as $assigned_user) {
          $text = "${invoker['full_name']}-მ დააფინანსა ${aim_name} ${amount} ლარით.";
          $text_en = "${invoker['full_name']} donated ${amount} GEL to ${aim_name}.";
          $this->notifyUserService->notifyUser($assigned_user->target_id, $invoker, $notification_type, $notification_type_en, $text, $text_en);
        }
      }
      else {
        $text = "${invoker['full_name']}-მ  დაგაფინანსათ ${amount} ლარით.";
        $text_en = "${invoker['full_name']} donated you ${amount} GEL.";
        $this->notifyUserService->notifyUser($user_id, $invoker, $notification_type, $notification_type_en, $text, $text_en);
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
