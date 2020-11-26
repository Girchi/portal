<?php

namespace Drupal\girchi_ged_transactions;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class SummaryGedCalculationService.
 */
class SummaryGedCalculationService {

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $database;

  /**
   * Constructs a new SummaryGedCalculationService object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   Logger messages.
   * @param \Drupal\Core\Database\Connection $database
   *   Connection.
   */
  public function __construct(LoggerChannelFactory $loggerFactory, Connection $database) {
    $this->loggerFactory = $loggerFactory->get('girchi_ged_transactions');
    $this->database = $database;
  }

  /**
   * SummaryGedCalculation.
   */
  public function summaryGedCalculation() {
    $arr = [];
    try {
      $this->database->query("SET SQL_MODE=''");
      $query = $this->database->select('ged_transaction_field_data', 'gt');
      $query->addExpression('sum(gt.ged_amount)', 'gedValue');
      $results = $query->execute()->fetchAll();
      $gedValue = $results[0]->gedValue;
      if (empty($gedValue)) {
        $gedValue = 0;
      }
      $gedPercentage = $gedValue / 50000000;
      $arr = [
        'gedValue' => $gedValue,
        'gedPercentage' => $gedPercentage,
      ];
      return $arr;
    }
    catch (\Exception $e) {
      $this->loggerFactory->error($e->getMessage());
    }
    return $arr;
  }

}
