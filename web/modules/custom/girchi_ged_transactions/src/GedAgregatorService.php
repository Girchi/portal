<?php

namespace Drupal\girchi_ged_transactions;

use Drupal\Core\Database\Connection;

/**
 * GedAgregatorService.
 */
class GedAgregatorService {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $databaseConnection;

  /**
   * GedAgregatorService constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection.
   */
  public function __construct(Connection $connection) {
    $this->databaseConnection = $connection;
  }

  /**
   * CalculateAndUpdateTotalGeds.
   */
  public function calculateAndUpdateTotalGeds($uid) {
    $connection = $this->databaseConnection;
    $prefix = $connection->tablePrefix();
    $query = $connection->query(
      "SELECT SUM(ged_amount) AS `ged_amount` FROM `{$prefix}ged_transaction_field_data` WHERE `user` = :id",
      [
        ':id' => $uid,
      ]
    );

    $result = $query->fetchAssoc();

    return $result['ged_amount'];
  }

}
