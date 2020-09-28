<?php

namespace Drupal\girchi_sms;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Utils service.
 */
class Utils {

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Database.
   *
   * @var \Drupal\Core\Database\Database
   */
  private $database;

  /**
   * Constructs an Utils object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   EntityTypeManager.
   * @param \Drupal\Core\Database\Connection $database
   *   Database.
   */
  public function __construct(LoggerChannelFactoryInterface $logger, EntityTypeManagerInterface $entityTypeManager, Connection $database) {
    $this->logger = $logger;
    $this->entityTypeManager = $entityTypeManager;
    $this->database = $database;
  }

  /**
   * Method description.
   */
  public function getNumbersByRegions($regions) {
    $query = $this->database->select('user__field_tel', 'tl');
    $query->fields('tl', ['field_tel_value']);
    if (!empty($regions)) {
      $regions = $this->loadChildRegions($regions);
      $query->leftJoin('user__field_region', 'rg', 'tl.entity_id = rg.entity_id');
      $query->condition('rg.field_region_target_id', $regions, 'IN');
    }
    $results = $query->execute()->fetchAll();
    $numbers = $this->normalizeNumbers($results);
    return $numbers;
  }

  /**
   * Load child regions.
   */
  private function loadChildRegions($regions) {
    $finalRegions = [];
    foreach ($regions as $region) {
      $childregions = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('regions', $region['target_id'], 1, FALSE);
      if (!empty($childregions)) {
        $regionIds = array_map(function ($a) {
          return $a->tid;
        }, $childregions);
        $finalRegions = array_merge($regionIds, $finalRegions);
      }
      else {
        $finalRegions[] = $region['target_id'];
      }
    }
    return $finalRegions;
  }

  /**
   * Normalize numbers.
   */
  private function normalizeNumbers($numbers) {
    $finalString = "";
    foreach ($numbers as $number) {
      $number = $number->field_tel_value;
      $number = preg_replace('/\s+/', '', $number);
      $number = str_replace("+", '', $number);
      if (strlen($number) == 12 && substr($number, 0, 3) == '995' || strlen($number) == 9 && substr($number, 0, 1) == '5') {
        $finalString .= "${number},";
      }
    }
    return $finalString;
  }

}
