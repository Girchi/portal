<?php

namespace Drupal\girchi_donations\Commands;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManager;
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
class GirchiDonationsCommands extends DrushCommands {


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
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   ET manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger.
   */
  public function __construct(EntityTypeManager $entityTypeManager, LoggerChannelFactoryInterface $loggerFactory) {
    parent::__construct();
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * Main command.
   *
   * @command girchi_donations:fix-relation
   * @aliases fix-relation
   */
  public function makeRelation() {
    try {
      $donations_array = [];
      $donation_storage = $this->entityTypeManager->getStorage('donation');
      $ged_t_storage = $this->entityTypeManager->getStorage('ged_transaction');
      $donation_entity_ids = $donation_storage->getQuery()
        ->condition('status', 'OK')
        ->condition('user_id', '0', '!=')
        ->sort('amount', 'DESC')
        ->execute();
      $ged_t_ids = $ged_t_storage->getQuery()
        ->condition('Description', 'Transaction was created by donation')
        ->condition('user_id', '0', '!=')
        ->condition('user', '0', '!=')
        ->sort('ged_amount', 'DESC')
        ->execute();
      $donation_entities = $donation_storage->loadMultiple($donation_entity_ids);
      $ged_ts = $ged_t_storage->loadMultiple($ged_t_ids);
      /** @var \Drupal\girchi_donations\Entity\Donation $donation */
      foreach ($donation_entities as $donation) {
        $uid = $donation->getUser()->id();
        $donations_array[$uid]['donations'][] = $donation;
      }
      /** @var \Drupal\girchi_ged_transactions\Entity\GedTransaction $transaction */
      foreach ($ged_ts as $transaction) {
        $uid = $transaction->get('user')->entity->id();
        if (isset($donations_array[$uid])) {
          $donations_array[$uid]['transaction'][] = $transaction->id();
        }
      }
      $i = 0;
      foreach ($donations_array as $array_item) {
        $d = $array_item['donations'];
        $t = $array_item['transaction'];
        if ($t !== NULL) {
          $i++;
          if (count($d) != count($t)) {
            array_pop($d);
          };
        }
      }
      dump($i);
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());
    }
  }

}
