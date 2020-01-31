<?php

namespace Drupal\girchi_referral\Commands;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
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
class GirchiReferralCommands extends DrushCommands {

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
   * Constructs a new SummaryGedCalculationService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   Logger messages.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactory $loggerFactory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $loggerFactory->get('girchi_referrals');
  }

  /**
   * Main command.
   *
   * @command girchi_referral:default-referral-date
   * @aliases default-referral-date
   */
  public function setReferralDate() {
    try {
      $user_storage = $this->entityTypeManager->getStorage('user');
      $uid = $user_storage->getQuery()
        ->condition('field_referral', NULL, 'IS NOT NULL')
        ->execute();
      $users = $user_storage->loadMultiple($uid);
      foreach ($users as $user) {
        $user->set('field_referral_date', date('Y-m-d', time()));
        $user->save();
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

  /**
   * Command to clean referrals.
   *
   * @command girchi_referral:clean-referrals
   * @aliases clean-referrals
   */
  public function cleanReferrals() {
    try {
      $user_storage = $this->entityTypeManager->getStorage('user');
      $users = $user_storage->getQuery()
        ->condition('field_referral', NULL, 'IS NOT NULL')
        ->execute();
      $users = $user_storage->loadMultiple($users);
      foreach ($users as $user) {
        if ($user->id() == $user->field_referral->target_id) {
          unset($user->field_referral);
          $user->save();
        }
        else {
          if ($user->field_referral->target_id) {
            $referral_user = $user_storage->load($user->field_referral->target_id);
            if ($referral_user->field_referral) {
              if ($user->id() == $referral_user->field_referral->target_id) {
                unset($user->field_referral->target_id);
                $user->save();
                unset($referral_user->field_referral->target_id);
                $referral_user->save();
              }
            }
          }
        }
      }
    }
    catch (\Exception $e) {
      $this->loggerFactory->error($e->getMessage());
    }
  }

}
