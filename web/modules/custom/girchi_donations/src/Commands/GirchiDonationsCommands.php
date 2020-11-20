<?php

namespace Drupal\girchi_donations\Commands;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManager;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Driver\Exception\Exception;
use Drupal\girchi_donations\Entity\RegularDonation;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
   * Database.
   *
   * @var \Drupal\Core\Database\Database
   */
  protected $database;

  /**
   * Queue manager.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManager
   */
  protected $queueManager;

  /**
   * Queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Construct.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   ET manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger.
   * @param \Drupal\Core\Database\Connection $database
   *   Database.
   * @param \Drupal\Core\Queue\QueueWorkerManager $queueManager
   *   Queue manager.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   Queue factory.
   */
  public function __construct(EntityTypeManager $entityTypeManager,
                              LoggerChannelFactoryInterface $loggerFactory,
                              Connection $database,
                              QueueWorkerManager $queueManager,
                              QueueFactory $queueFactory) {
    parent::__construct();
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerFactory = $loggerFactory;
    $this->database = $database;
    $this->queueManager = $queueManager;
    $this->queueFactory = $queueFactory;
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
      foreach ($donations_array as $array_item) {
        $d = $array_item['donations'];
        $t = $array_item['transaction'];
        if ($t !== NULL) {
          if (count($d) != count($t)) {
            array_pop($d);
          };
          $length = count($d);
          for ($i = 0; $i < $length; $i++) {
            $d[$i]->set('field_ged_transaction', $t[$i])->save();
          }
        }
      }

    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());
    }
  }

  /**
   * Main command.
   *
   * @command girchi_donations:restore-dates
   * @aliases restore-dates
   */
  public function restoreDates(InputInterface $input, OutputInterface $output) {
    try {
      $regd_storage = $this->entityTypeManager->getStorage('regular_donation');
      $group = $regd_storage->getQuery()
        ->orConditionGroup()
        ->condition('status', 'ACTIVE', '=')
        ->condition('status', 'PAUSED', '=');
      $regd_ids = $regd_storage->getQuery()
        ->condition($group)
        ->condition('next_payment_date', $this->getMothDays(), 'IN')
        ->execute();
      $regds = $regd_storage->loadMultiple($regd_ids);
      $progress_bar = new ProgressBar($output, count($regd_ids));

      // Starts and displays the progress bar.
      $progress_bar->start();
      /** @var \Drupal\girchi_donations\Entity\RegularDonation $regd */
      foreach ($regds as $regd) {
        $progress_bar->setMessage(sprintf("\n Fixing date for %s \n", $regd->getOwner()
          ->get('name')->value));

        $carbon_date = Carbon::createFromFormat(DateTimeItemInterface::DATE_STORAGE_FORMAT, $regd->get('next_payment_date')->value);
        $next_payment_date = $carbon_date->addMonths($regd->get('frequency')->value)
          ->toDateTime()
          ->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE))
          ->format(DateTimeItemInterface::DATE_STORAGE_FORMAT);
        $regd->set('next_payment_date', $next_payment_date);

        $regd->save();
        $progress_bar->advance();
      }

      $progress_bar->finish();

    }
    catch (\Exception $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());

    }

  }

  /**
   * Main command.
   *
   * @command girchi_donations:delete-regs
   * @aliases delete-regs
   */
  public function forceDelete(InputInterface $input, OutputInterface $output, $ids, $options = ['show' => FALSE]) {
    try {

      $ids = explode(',', $ids);
      if (!empty($ids)) {
        if ($options['show']) {
          $user_storage = $this->entityTypeManager->getStorage('user');
          $users = $user_storage->loadMultiple($ids);

          /** @var \Drupal\user\Entity\User $user */
          foreach ($users as $user) {
            $output->writeln(sprintf("Deleting regular donations for %s", $user->getAccountName()));
          }
        }

        $regd_storage = $this->entityTypeManager->getStorage('regular_donation');
        $regd_ids = $regd_storage->getQuery()
          ->condition('user_id', $ids, 'IN')
          ->execute();

        if (!empty($regd_ids)) {
          $progress_bar = new ProgressBar($output, count($regd_ids));
          $regd_entities = $regd_storage->loadMultiple($regd_ids);
          $progress_bar->start();
          /** @var \Drupal\girchi_donations\Entity\RegularDonation $regd_entity */
          foreach ($regd_entities as $regd_entity) {
            $regd_entity->delete();
            $progress_bar->advance();
          }
          $progress_bar->finish();
        }
      }

    }
    catch (\Exception $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());

    }
  }

  /**
   * Main command.
   *
   * @command girchi_donations:fix-aims
   * @aliases fix-aims
   */
  public function fixAims() {
    try {
      $reg_donations_storage = $this->entityTypeManager
        ->getStorage('regular_donation');
      $reg_donations_ids = $reg_donations_storage->getQuery()
        ->condition('status', 'FAILED', '!=')
        ->condition('status', 'INITIAL', '!=')
        ->execute();

      $term = $this->entityTypeManager
        ->getStorage('taxonomy_term')->load(1035);
      $reg_donations = $reg_donations_storage->loadMultiple($reg_donations_ids);
      foreach ($reg_donations as $reg_donation) {
        if (!$reg_donation->get('aim_id')->entity) {
          $reg_donation->set('aim_id', $term);
          $reg_donation->save();
        }
      }
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());
    }
  }

  /**
   * Process regular donations in queue command.
   *
   * @command girchi_donations:process_reg_donation
   * @aliases process-donation
   * @options Integer An option that takes user id.
   */
  public function processRegDonation($userId = NULL) {
    try {
      if ($userId != NULL) {
        $userArray[] = $userId;
      }
      else {
        $userArray = [
          7279,
          299,
          695,
          2695,
          3774,
          1066,
          3363,
          4086,
          168,
          2049,
          731,
          164,
          2688,
          1442,
          2964,
          102,
          2408,
          3301,
          513,
          136,
          3584,
          3664,
          193,
          180,
          621,
          223,
          1800,
          4029,
          147,
          7688,
          197,
          2458,
          3020,
          3192,
          3171,
          4148,
          4519,
          1380,
          265,
          7102,
          183,
          441,
          3820,
          2129,
          3855,
          2751,
          944,
          2105,
          3892,
          1304,
          496,
          2053,
          597,
          548,
          161,
          1847,
          1010,
          1768,
          4150,
          1173,
          3888,
          1719,
          353,
          454,
          3638,
          453,
          82,
          3540,
          29,
          3674,
          882,
          463,
          198,
          1510,
          2523,
          3364,
          1911,
          169,
          564,
          1223,
          1899,
          1661,
          1357,
          1242,
          870,
          186,
          647,
          1367,
          12610,
          501,
          2377,
          574,
          2982,
          668,
          2400,
          2804,
          298,
          365,
          1019,
          2416,
          1115,
          718,
          5662,
          4927,
          281,
          5392,
          2829,
          1143,
          580,
          2380,
          30,
          2370,
          604,
          2047,
          3432,
          1178,
          3955,
          1833,
          2992,
          594,
          413,
          756,
          179,
          3592,
          735,
          318,
          381,
          6396,
          2759,
          937,
          13,
          1145,
          2766,
          891,
          3628,
          2242,
          2660,
          736,
          4533,
          1247,
          1475,
          871,
          2454,
          439,
          2035,
          216,
          1160,
          576,
          5769,
          244,
          1393,
          2899,
          4257,
          54,
          3816,
          249,
          15,
          894,
          1547,
          320,
          208,
          2863,
          1660,
          14,
          3580,
          1324,
          2678,
          2282,
          4628,
          1241,
          3817,
          577,
          349,
          1035,
          4437,
          159,
          379,
          996,
          1004,
          819,
          288,
          1179,
          1267,
          258,
          269,
          1035,
          991,
        ];
      }

      $queue = $this->queueFactory->get('regular_donation_processor');
      $queue_worker = $this->queueManager->createInstance('regular_donation_processor');

      $this->database->query("SET SQL_MODE=''");
      $regQuery = $this->database->select('queue', 'x')
        ->fields('x', ['name', 'item_id', 'data'])
        ->condition('x.name', 'regular_donation_processor', '=');

      $data = $regQuery->execute()->fetchAll();
      foreach ($data as $item) {
        $regDonationEntity = unserialize($item->data);
        if ($regDonationEntity instanceof RegularDonation) {
          if (in_array($regDonationEntity->getOwnerId(), $userArray)) {
            // Process and delete item.
            $queue_worker->processItem($regDonationEntity);
            $queue->deleteItem($item);
          }
        }
      }
    }
    catch (Exception $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());
    }
    catch (PluginException $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());
    }
  }

  /**
   * Process regular donations in queue command.
   *
   * @command girchi_donations:update_reg_donations
   * @aliases update-donations
   */
  public function updateRegDonations() {
    try {
      $this->database->query("SET SQL_MODE=''");
      $regQuery = $this->database->select('queue', 'x')
        ->fields('x', ['name', 'item_id', 'data'])
        ->condition('x.name', 'regular_donation_processor', '=');
      $data = $regQuery->execute()->fetchAll();
      foreach ($data as $item) {
        $regDonationEntity = unserialize($item->data);
        if ($regDonationEntity instanceof RegularDonation) {
          $date = $regDonationEntity->get('next_payment_date')->value;
          $frequency = $regDonationEntity->get('frequency')->value;
          $carbon_date = Carbon::createFromFormat(DateTimeItemInterface::DATE_STORAGE_FORMAT, $date);
          $next_payment_date = $carbon_date->addMonths($frequency)
            ->toDateTime()
            ->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE))
            ->format(DateTimeItemInterface::DATE_STORAGE_FORMAT);
          $regDonationEntity->set('next_payment_date', $next_payment_date);
          try {
            $regDonationEntity->save();
          }
          catch (\Exception $e) {
            $this->loggerFactory->get('girchi_donations')->error($e->getMessage());
            $this->loggerFactory->get('girchi_donations')->error("Regular donation was deleted with id: " . $regDonationEntity->id());
          }

        }
      }
    }
    catch (Exception $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());
    }
  }

  /**
   * Helper.
   */
  public function getMothDays() {
    $response = [];
    $period = CarbonPeriod::create(Carbon::createFromDate(2020, 2, 1), Carbon::yesterday());

    /** @var \Carbon\Carbon $day */
    foreach ($period as $day) {
      $response[] = $day->toDateTime()
        ->format(DateTimeItemInterface::DATE_STORAGE_FORMAT);
    }

    return $response;
  }

}
