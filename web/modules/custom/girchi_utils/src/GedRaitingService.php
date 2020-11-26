<?php

namespace Drupal\girchi_utils;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Class GedRaitingService.
 */
class GedRaitingService {

  /**
   * Database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new PartyListCalculatorService object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   LoggerChannelFactoryInterface.
   */
  public function __construct(Connection $database,
                              LoggerChannelFactoryInterface $loggerFactory) {
    $this->database = $database;
    $this->loggerFactory = $loggerFactory->get('girchi_utils');
  }

  /**
   * CalculateRankRating.
   *
   * @param int $uid
   *   User id.
   *
   * @return int
   *   Rating.
   */
  public function calculateRankRating($uid) {
    try {
      $this->database->query("SET SQL_MODE=''");
      $query = $this->database->select('users', 'u');

      $query->leftJoin('user__field_last_name', 'ln', 'u.uid = ln.entity_id');
      $query->leftJoin('user__field_ged', 'ged', 'u.uid = ged.entity_id');
      $query->addField('ged', 'field_ged_value', 'ged_value');
      $query->addField('ln', 'field_last_name_value', 'last_name');
      $query->addField('u', 'uid', 'user_id');
      $query
        ->orderBy('ged_value', 'DESC')
        ->orderBy('last_name', 'ASC');
      $results = $query->execute()->fetchAll();

      // Since result of the query is multi-dimensional array
      // First we need to return the values
      // From a single column in the input array
      // And Then return array key to get rank rating for given user.
      $array_of_ids = array_column($results, 'user_id');
      $found_key = array_search($uid, $array_of_ids);

      return (int) $found_key + 1;

    }
    catch (\Exception $e) {
      $this->loggerFactory->error($e->getMessage());
    }
    return 0;
  }

}
