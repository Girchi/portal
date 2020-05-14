<?php

namespace Drupal\girchi_users\Commands;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
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
class GirchiUsersCommands extends DrushCommands {
  /**
   * EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  private $loggerFactory;

  /**
   * QueueFactory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * EntityTypeManager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   EntityManager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Logger.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   QueueFactory.
   */
  public function __construct(EntityTypeManager $entityTypeManager, LoggerChannelFactoryInterface $loggerChannelFactory, QueueFactory $queueFactory) {
    parent::__construct();
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerFactory = $loggerChannelFactory;
    $this->queueFactory = $queueFactory;
  }

  /**
   * Main command.
   *
   * @command girchi_users:fix-field-tel
   * @aliases fix-field-tel
   */
  public function fixFieldTel() {
    $user_storage = $this->entityTypeManager->getStorage('user');
    $users_id = $user_storage->getQuery()
      ->condition('field_phone', NULL, 'IS NOT NULL')
      ->execute();
    $users = $user_storage->loadMultiple($users_id);
    foreach ($users as $user) {
      $old_value = $user->get('field_phone')->value;
      $user->get('field_tel')->value = NULL == $user->get('field_tel')->value ? $old_value : $user->get('field_tel')->value;
      $user->save();
    }
  }

  /**
   * Main command.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   Input.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   Output.
   *
   * @command girchi_users:publicity
   * @aliases publicity
   */
  public function publicity(InputInterface $input, OutputInterface $output) {
    try {
      $users = $this->entityTypeManager->getStorage('user')->loadMultiple();
      $progress_bar = new ProgressBar($output, count($users));
      $progress_bar->start();
      foreach ($users as $user) {
        $user->set('field_publicity', TRUE);
        $user->save();
        $progress_bar->advance();
      }
      $progress_bar->finish();
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('girchi_users')->error($e->getMessage());
    }
  }

  /**
   * Main command.
   *
   * @command girchi_users:badges
   * @aliases user-badges
   */
  public function userBadges() {
    try {
      $users = $this->entityTypeManager->getStorage('user')->loadMultiple();
      $queue = $this->queueFactory->get('user_badges_queue');

      $donation_storage = $this->entityTypeManager->getStorage('donation');
      $regular_donation_storage = $this->entityTypeManager->getStorage('regular_donation');

      foreach ($users as $user) {
        $term = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['name' => 'პორტალის წევრი']);
        $tid = reset($term)->id();
        $queue->createItem(['uid' => $user->id(), 'tid' => $tid]);

        if ($user->get('field_politician')->value == TRUE) {
          $term = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['name' => 'პოლიტიკოსი']);
          $tid = reset($term)->id();
          $queue->createItem(['uid' => $user->id(), 'tid' => $tid]);
        }

        $single_donation = $donation_storage->getQuery()
          ->condition('user_id', $user->id(), '=')
          ->condition('field_donation_type', '0', '=')
          ->execute();
        $regular_donation = $regular_donation_storage->getQuery()
          ->condition('user_id', $user->id(), '=')
          ->condition('status', 'ACTIVE', '=')
          ->execute();

        if (!empty($single_donation)) {
          $term = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['name' => 'პარტნიორი - ერთჯერადი დამფინანსებელი']);
          $tid = reset($term)->id();
          $queue->createItem(['uid' => $user->id(), 'tid' => $tid]);
        }

        if (!empty($regular_donation)) {
          $term = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['name' => 'პარტნიორი - მრავალჯერადი დამფინანსებელი']);
          $tid = reset($term)->id();
          $queue->createItem(['uid' => $user->id(), 'tid' => $tid]);
        }

      }

    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->get('girchi_users')->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->get('girchi_users')->error($e->getMessage());
    }

  }

}
