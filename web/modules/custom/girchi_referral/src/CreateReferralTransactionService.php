<?php

namespace Drupal\girchi_referral;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\girchi_donations\Entity\Donation;

/**
 * Class CreateReferralTransactions.
 */
class CreateReferralTransactionService {
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
   * Function to create referral transaction.
   */
  public function createReferralTransaction($user, $referral_id, Donation $donation) {
    $donation_entity = $donation->id();
    $donation_amount = $donation->getAmount();
    $ref_benefit = $donation_amount / 10;
    /** @var \Drupal\node\Entity\NodeStorage */
    $node_storage = $this->entityTypeManager->getStorage('node');
    $referral_transaction = $node_storage->create([
      'type' => 'referral_transaction',
      'field_user' => $user,
      'field_referral' => $referral_id,
      'field_donation' => $donation_entity,
      'field_amount_of_money' => $ref_benefit,
      'title' => 'Referral transaction',
    ]);
    try {
      $referral_transaction->save();
      $this->loggerFactory->info('Referral transaction was created');

    }
    catch (\Exception $exception) {
      $this->loggerFactory->error($exception);
    }

  }

  /**
   * CalculateAndUpdateTotalGeds.
   */
  public function countReferralsMoney($uid) {
    try {
      $node_storage = $this->entityTypeManager->getStorage('node');
      $referral_transactions = $node_storage->loadByProperties(['field_referral' => $uid]);
      $sum_of_money = 0;
      foreach ($referral_transactions as $referral_transaction) {
        $amount_of_money = $referral_transaction->get('field_amount_of_money')->value;
        $sum_of_money = $sum_of_money + $amount_of_money;
      }
      $user = $this->entityTypeManager->getStorage('user')->load($uid);
      $user->set('field_referral_benefits', $sum_of_money);
      $user->save();
      $user_first_name = $user->get('field_first_name')->value;
      $user_last_name = $user->get('field_last_name')->value;
      $info = $sum_of_money . " ლარი ჩაერიცხა " . $user_first_name . ' ' . $user_last_name . ' -ს';
      $this->loggerFactory->info($info);

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
