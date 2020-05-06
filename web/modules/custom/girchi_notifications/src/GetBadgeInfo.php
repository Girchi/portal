<?php

namespace Drupal\girchi_notifications;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class GetBadgeInfo.
 */
class GetBadgeInfo {
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
   * Constructs a new GetUserInfoService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   Logger messages.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactory $loggerFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerFactory = $loggerFactory->get('girchi_notifications');
  }

  /**
   * GetBadgeInfo.
   *
   * @param int $badge_id
   *   badge_id.
   *
   * @return array
   */
  public function getBadgeInfo($badge_id) {
    try {
      $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($badge_id);
      $badge_name = $term->get('name')->value;
      $badge_logo = $term->get('field_logo')->entity->getFileUri();

      return [
        'badge_name' => $badge_name,
        'badge_img' => $badge_logo,
        'badge_id' => $badge_id,
      ];
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->error($e->getMessage());
    }
    return [];

  }

}
