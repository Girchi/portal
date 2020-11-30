<?php

namespace Drupal\girchi_users\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\girchi_notifications\GetUserInfoService;
use Drupal\girchi_notifications\NotifyUserService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes UserBadge tasks.
 *
 * @QueueWorker(
 *   id = "special_transactions",
 *   title = @Translation("Special transactions"),
 *   cron = {"time" = 60}
 * )
 */
class SpecialTransactionsQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {
  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected  $entityTypeManager;

  /**
   * NotifyUserService.
   *
   * @var \Drupal\girchi_notifications\NotifyUserService
   */
  protected $notifyUser;

  /**
   * GetUserInfoService.
   *
   * @var \Drupal\girchi_notifications\GetUserInfoService
   */
  protected $userInfo;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entityTypeManager,
                              NotifyUserService $notifyUserService,
                              GetUserInfoService $getUserInfoService,
                              LoggerChannelFactory $loggerChannelFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->notifyUser = $notifyUserService;
    $this->userInfo = $getUserInfoService;
    $this->loggerFactory = $loggerChannelFactory->get('girchi_users');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('girchi_notifications.notify_user'),
      $container->get('girchi_notifications.get_user_info'),
      $container->get('logger.factory')
    );

  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    try {
      $uid = $data['uid'];
      $special_user = $data['special_uid'];
      $ged_amount = (int) $data['amount'];

      $ged_transactions_storage = $this->entityTypeManager->getStorage('ged_transaction');
      $transaction_type_id = $this->entityTypeManager->getStorage('taxonomy_term')->load(944);
      $transaction_type_id2 = $this->entityTypeManager->getStorage('taxonomy_term')->load(1370);

      $new_girchi_transaction = $ged_transactions_storage->create([
        'user_id' => "1",
        'user' => $uid,
        'ged_amount' => 1379,
        'Description' => "გირჩის ბიუჯეტიდან ჩარიცხვა",
        'title' => 'Girchi',
        'name' => 'Girchi',
        'transaction_type' => $transaction_type_id,
        'status' => TRUE,
      ]);
      $new_girchi_transaction->save();

      $new_transaction = $ged_transactions_storage->create([
        'user_id' => "1",
        'user' => $uid,
        'ged_amount' => $ged_amount,
        'Description' => "ლევან ჯგერენაიამ გადაურიცხა",
        'title' => 'Levan Jgerenaia',
        'name' => 'Levan Jgerenaia',
        'transaction_type' => $transaction_type_id,
        'status' => TRUE,
      ]);
      $new_transaction->save();

      $return_transaction = $ged_transactions_storage->create([
        'user_id' => "1",
        'user' => $special_user,
        'ged_amount' => -$ged_amount,
        'Description' => "უკუგატარება",
        'title' => 'Levan Jgerenaia',
        'name' => 'Levan Jgerenaia',
        'transaction_type' => $transaction_type_id2,
        'status' => TRUE,
      ]);
      $return_transaction->save();
    }
    catch (\Exception $e) {
      $this->loggerFactory->error($e->getMessage());

    }

  }

}
