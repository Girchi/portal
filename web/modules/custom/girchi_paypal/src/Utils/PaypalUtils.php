<?php

namespace Drupal\girchi_paypal\Utils;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Service for paypal utils.
 */
class PaypalUtils {
  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;
  /**
   * LoggerChannelFactory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerChannelFactory;

  /**
   * Construct.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   EntityTypeManager.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerChannelFactory
   *   LoggerChannelFactory.
   */
  public function __construct(EntityTypeManager $entityTypeManager, LoggerChannelFactory $loggerChannelFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerChannelFactory = $loggerChannelFactory->get('girchi_paypal');
  }

  /**
   * Checks if user exits and if it's politician.
   *
   * @param mixed $politicianId
   *   Politician id.
   *
   * @return bool
   *   Returns true if user exits and it's politician
   */
  public function checkPolitcian($politicianId) {
    try {
      $user_storage = $this->entityTypeManager->getStorage('user');
      $user = $user_storage->load($politicianId);
      if ($user && $user->field_politician->value == TRUE) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
    catch (\Exception $e) {
      $this->loggerChannelFactory->info($e->getMessage());
    }

  }

  /**
   * Checks if donation aim exits.
   *
   * @param mixed $donationAimId
   *   Donation aim id.
   *
   * @return bool
   *   Returns true if donation aim exits
   */
  public function checkDonationAim($donationAimId) {
    try {
      $taxonomy_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      $taxonomy = $taxonomy_storage->load($donationAimId);
      if ($taxonomy) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
    catch (\Exception $e) {
      $this->loggerChannelFactory->info($e->getMessage());
    }

  }

}
