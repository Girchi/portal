<?php

namespace Drupal\girchi_users;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class CustomBadgesService.
 */
class CustomBadgesService {
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
   * CreateGedTransaction constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   EntityTypeManager.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerChannelFactory
   *   Logger factory.
   */
  public function __construct(EntityTypeManager $entityTypeManager, LoggerChannelFactory $loggerChannelFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerFactory = $loggerChannelFactory->get('girchi_users');
  }

  /**
   * Funtion for getting custom badges.
   *
   * @param bool $full
   *   full data.
   *
   * @return array
   *   Options.
   */
  public function getCustomBadges($full = TRUE) {
    try {
      $options = [];
      /** @var \Drupal\taxonomy\TermStorage $term_storage */
      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      $terms = $term_storage->loadTree('Badges', 0, NULL, TRUE);

      foreach ($terms as $term) {
        $status = $term->get('field_publicity')->value;
        if ($status == FALSE) {
          if ($full == TRUE) {
            $options[$term->id()] = [
              'data_type' => 1,
              'badge' => $term->getName(),
              'id' => $term->id(),
            ];
          }
          else {
            $options[$term->id()] = $term->getName();
          }

        }
      }
      return $options;

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
