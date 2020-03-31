<?php

namespace Drupal\girchi_notifications;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class GetUserInfoService.
 */
class GetUserInfoService {
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
   * Get user into.
   *
   * @return array|null
   *   user_info.
   */
  public function getUserInfo($uid) {
    try {
      $user = $this->entityTypeManager->getStorage('user')->load($uid);
      if (!empty($user)) {
        $user_name = $user->get('field_first_name')->value;
        $user_surname = $user->get('field_last_name')->value;
        $full_name = $user_name ? $user_name . ' ' . $user_surname : '';
        if ($user->get('user_picture')->entity) {
          $style = $user->get('user_picture')->entity->getFileUri();
          $user_picture = file_create_url($style);
        }
        else {
          $user_picture = file_create_url(drupal_get_path('theme', 'girchi') . '/images/avatar.png');
        }

        return [
          'uid' => $user->id(),
          'full_name' => $full_name,
          'image' => $user_picture,
        ];
      }
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
