<?php

namespace Drupal\girchi_donations;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Export donation service.
 */
class ExportDonationService {
  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * ExportDonationService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactoryInterface $loggerFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerFactory = $loggerFactory;

  }

  /**
   * Export donation service.
   *
   * @param int $start_month
   *   Start month.
   * @param int $end_month
   *   End month.
   * @param string $donation_source
   *   Donation source.
   *
   * @return array
   *   Return array.
   */
  public function exportDonationService($start_month, $end_month, $donation_source) {
    try {
      $user_storage = $this->entityTypeManager->getStorage('user');
      $donation_storage = $this->entityTypeManager->getStorage('donation');

      $donation_entity_ids_query = $donation_storage->getQuery()
        ->condition('status', 'OK')
        ->condition('user_id', '0', '!=')
        ->condition('field_source', $donation_source);
      $group = $donation_entity_ids_query
        ->andConditionGroup()
        ->condition('created', $start_month, '>=')
        ->condition('created', $end_month, '<=');
      $donation_entity_ids_query->condition($group);
      $donation_entity_ids = $donation_entity_ids_query->execute();
      $donations = $donation_storage->loadMultiple($donation_entity_ids);
      $donation_records = [];

      foreach ($donations as $donation) {
        $m = date('m', $donation->get('created')->value);
        $donation_amount = intval($donation->getAmount());
        $uid = $donation->getUser()->id();
        $user = $user_storage->load($uid);
        $user_name = $user->get('field_first_name')->value;
        $user_surname = $user->get('field_last_name')->value;
        $full_name = $user_name ? $user_name . ' ' . $user_surname : '';

        if (empty($full_name)) {
          continue;
        }

        if (array_key_exists($uid, $donation_records)) {
          if (isset($donation_records[$uid]['donation'][$m])) {
            $donation_records[$uid]['donation'][$m] += $donation_amount;
          }
          else {
            $donation_records[$uid]['donation'][$m] = $donation_amount;
          }
        }
        else {
          $donation_records[$uid] = [
            'uid' => $uid,
            'donation' => [
              $m => $donation_amount,
            ],
            'full_name' => $full_name,
          ];
        }
      }

      return $donation_records;
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());
    }

    return [];

  }

}
