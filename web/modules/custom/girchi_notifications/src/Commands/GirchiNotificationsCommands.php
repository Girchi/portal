<?php

namespace Drupal\girchi_notifications\Commands;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drush\Commands\DrushCommands;

/**
 * Class GirchiNotificationsCommands.
 */
class GirchiNotificationsCommands extends DrushCommands {
  /**
   * EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ConfigFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;


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
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   ConfigFactory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactory $configFactory, LoggerChannelFactoryInterface $loggerFactory) {
    parent::__construct();
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
    $this->loggerFactory = $loggerFactory->get('girchi_notifications');
  }

  /**
   * Main command.
   *
   * @command girchi_donations:default-aim
   * @aliases default-aim
   */
  public function defaultAimUser() {
    try {
      $user = $this->configFactory->get('om_site_settings.site_settings')->get('default_receiver');
      $taxonomy_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      $tid = $taxonomy_storage->getQuery()
        ->condition('vid', 'donation_issues')
        ->execute();
      $terms = $taxonomy_storage->loadMultiple($tid);
      foreach ($terms as $term) {
        $term->set('field_user', $user);
        $term->save();
      }
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->error($e->getMessage());

    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->error($e->getMessage());

    }
    catch (EntityStorageException $e) {
      $this->loggerFactory->error($e->getMessage());

    }

  }

}
