<?php

namespace Drupal\girchi_ged_transactions;

class GedAgregatorService
{
  public function calculateAndUpdateTotalGeds($uid)
  {
    $connection = \Drupal::database();
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