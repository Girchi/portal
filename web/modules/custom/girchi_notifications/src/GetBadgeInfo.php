<?php

namespace Drupal\girchi_notifications;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\girchi_users\Constants\BadgeConstants;
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
   *   Badge_id.
   *
   * @return array
   *   Badge_info.
   */
  public function getBadgeInfo($badge_id) {
    try {
      $language = $this->languageManager->getCurrentLanguage()->getId();
      $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($badge_id);
      $badge_name_en = $term->getName();
      $icon = $term->get('field_icon_class')->value;

      $logo_svg = [
        BadgeConstants::PORTAL_MEMBER => '/themes/custom/girchi/images/badge-user.svg',
        BadgeConstants::CULTIVATION => '/themes/custom/girchi/images/badge-weed.svg',
        BadgeConstants::POLITICIAN => '/themes/custom/girchi/images/badge-politician.svg',
        BadgeConstants::SINGLE_CONTRIBUTOR => '/themes/custom/girchi/images/badge-partner.svg',
        BadgeConstants::REGULAR_CONTRIBUTOR => '/themes/custom/girchi/images/badge-partner-multiple.svg',
        // TODO:: Change with right svg.
        BadgeConstants::TESLA => '/themes/custom/girchi/images/badge-user.svg',
      ];

      if ($language === 'ka' && $term->hasTranslation('ka')) {
        $badge_name = $term->getTranslation('ka')->getName();
      }
      else {
        $badge_name = $badge_name_en;
      }

      return [
        'badge_name' => $badge_name,
        'badge_name_en' => $badge_name_en,
        'badge_id' => $badge_id,
        'icon' => $icon,
        'logo_svg' => $logo_svg,
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
