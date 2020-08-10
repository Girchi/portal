<?php

namespace Drupal\girchi_ged_transactions\Commands;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class GirchiGedTransactionsCommands extends DrushCommands {

  /**
   * EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * LoggerFactory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Construct.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   ET manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactoryInterface $loggerFactory) {
    parent::__construct();
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * Main command.
   *
   * @command girchi_ged_transactions:fix-transaction-type
   * @aliases fix-transaction-type
   */
  public function setTransactionType($options = ['ged_description' => NULL, 'term_name' => NULL]) {
    $ged_transactions_storage = $this->entityTypeManager->getStorage('ged_transaction');
    $ged_transactions_ids = $ged_transactions_storage->getQuery()
      ->condition('Description', $options['ged_description'], 'CONTAINS')
      ->execute();

    $ged_transactions = $ged_transactions_storage->loadMultiple($ged_transactions_ids);

    /** @var \Drupal\taxonomy\TermStorage $transaction_types_storage */
    $transaction_type_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $transaction_type_id = $transaction_type_storage->getQuery()
      ->condition('vid', 'transaction_type')
      ->condition('name', $options['term_name'])
      ->execute();

    foreach ($ged_transactions as $ged_transaction) {
      $ged_transaction->set("transaction_type", reset($transaction_type_id));
      $ged_transaction->save();
    }
  }

  /**
   * Main command.
   *
   * @command girchi_ged_transactions:fix-donation-transactions
   * @aliases fix-donation-transactions
   */
  public function setTypeForDonationTransactions() {
    $ged_transactions_storage = $this->entityTypeManager->getStorage('ged_transaction');
    $ged_transactions_ids = $ged_transactions_storage->getQuery()
      ->condition('Description', 'Transaction was created by donation', '=')
      ->execute();

    $ged_transactions = $ged_transactions_storage->loadMultiple($ged_transactions_ids);

    $transaction_type_id = $this->entityTypeManager->getStorage('taxonomy_term')->load(1369);

    foreach ($ged_transactions as $ged_transaction) {
      $ged_transaction->set("transaction_type", $transaction_type_id);
      $ged_transaction->save();
    }
  }

  /**
   * Fix transaction type for regular donations.
   *
   * @command girchi_ged_transactions:fix-reg-transaction-type
   * @aliases fix-reg-transaction-type
   */
  public function fixRegTransactionType() {
    try {
      $ged_t_storage = $this->entityTypeManager->getStorage('ged_transaction');
      $ged_transaction_ids = $ged_t_storage->getQuery()
        ->condition('transaction_type', '1360', '=')
        ->execute();
      $ged_transactions = $ged_t_storage->loadMultiple($ged_transaction_ids);
      $transaction_type_id = $this->entityTypeManager->getStorage('taxonomy_term')->load(1369);

      foreach ($ged_transactions as $transaction) {
        $transaction->set('transaction_type', $transaction_type_id);
        $transaction->save();
      }

    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->get('girchi_ged_transactions')->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->get('girchi_ged_transactions')->error($e->getMessage());
    }
    catch (EntityStorageException $e) {
      $this->loggerFactory->get('girchi_ged_transactions')->error($e->getMessage());
    }
  }

}
