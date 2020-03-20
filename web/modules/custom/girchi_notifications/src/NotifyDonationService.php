<?php

namespace Drupal\girchi_notifications;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\StringTranslation\TranslationInterface;

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
   * Translation.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * Constructs a new SummaryGedCalculationService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   Logger messages.
   * @param \Drupal\girchi_notifications\NotifyUserService $notifyUserService
   *   Notify user service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   Translation.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager,
                              LoggerChannelFactory $loggerFactory,
                              NotifyUserService $notifyUserService,
                              TranslationInterface $translation) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerFactory = $loggerFactory->get('girchi_notifications');
    $this->notifyUserService = $notifyUserService;
    $this->stringTranslation = $translation;
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
   * @param int $user
   *   User.
   * @param int $donation_aim
   *   Donation aim.
   */
  public function notifyDonation($type, array $invoker, $amount, $user, $donation_aim) {
    try {
      if ($type == 1) {
        $taxonomy_storage = $this->entityTypeManager->getStorage('taxonomy_term')->load($donation_aim);
        $aim_name = $taxonomy_storage->get('name')->value;
        foreach ($taxonomy_storage->get('field_user') as $assigned_user) {
          $text = "${invoker['full_name']} donated ${amount} GEL to ${aim_name}";
          $this->notifyUserService->notifyUser($assigned_user->target_id, $invoker, 'donation', $text);
        }
      }
      else {
        $text = $this->stringTranslation->translate("${invoker['full_name']} donated you ${amount} GEL");
        $this->notifyUserService->notifyUser($user, $invoker, 'donation', $text);
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
