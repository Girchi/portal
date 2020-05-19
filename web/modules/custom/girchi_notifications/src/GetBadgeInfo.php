<?php

namespace Drupal\girchi_notifications;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\language\ConfigurableLanguageManager;

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
   * Language.
   *
   * @var \Drupal\language\ConfigurableLanguageManager
   */
  protected $languageManager;

  /**
   * Constructs a new GetUserInfoService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   Logger messages.
   * @param \Drupal\language\ConfigurableLanguageManager $languageManager
   *   ConfigurableLanguageManager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager,
                              LoggerChannelFactory $loggerFactory,
                              ConfigurableLanguageManager $languageManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerFactory = $loggerFactory->get('girchi_notifications');
    $this->languageManager = $languageManager;
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
      $language = $this->languageManager->getCurrentLanguage()->getId();
      $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($badge_id);
      $badge_logo = $term->get('field_logo')->entity->getFileUri();
      $badge_name_en = $term->getName();

      if ($language === 'ka' && $term->hasTranslation('ka')) {
        $badge_name = $term->getTranslation('ka')->getName();
      }
      else {
        $badge_name = $badge_name_en;
      }

      return [
        'badge_name' => $badge_name,
        'badge_name_en' => $badge_name_en,
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
