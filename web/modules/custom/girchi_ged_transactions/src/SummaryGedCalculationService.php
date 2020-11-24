<?php

namespace Drupal\girchi_ged_transactions;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class SummaryGedCalculationService.
 */
class SummaryGedCalculationService {

  /**
   * EntityTypeManagerInterface definition.
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
   * Database.
   *
   * @var \Drupal\Core\Database\Database
   */
  protected $database;

  /**
   * Constructs a new SummaryGedCalculationService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   Logger messages.
   * @param \Drupal\Core\Database\Connection $database
   *   Database.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactory $loggerFactory, Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $loggerFactory->get('girchi_ged_transactions');
    $this->database = $database;
  }

  /**
   * SummaryGedCalculation.
   */
  public function summaryGedCalculation() {
    $gedValue = 0;
    $gedPercentage = 0;
    $arr = [];
    try {

      $this->database->query("SET SQL_MODE=''");
      $sth = $this->database->select('user__field_ged', 'field_ged')
        ->fields('field_ged', ['field_ged_value']);

      $sth->addExpression('sum(field_ged.field_ged_value)', 'total_count');
      $data = $sth->execute()->fetchAll();
      if (isset($data[0]->total_count)) {
        $gedValue = $data[0]->total_count;
        $gedPercentage = $gedValue / 50000000;
      }

      $arr = [
        'gedValue' => $gedValue,
        'gedPercentage' => $gedPercentage,
      ];

    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->error($e->getMessage());
    }
    return $arr;
  }

}
