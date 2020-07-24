<?php

namespace Drupal\girchi_users\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\girchi_notifications\Constants\NotificationConstants;
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
   * {@inheritdoc}
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entityTypeManager,
                              NotifyUserService $notifyUserService,
                              GetUserInfoService $getUserInfoService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->notifyUser = $notifyUserService;
    $this->userInfo = $getUserInfoService;
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
      $container->get('girchi_notifications.get_user_info')
    );

  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $uid = $data['uid'];
    $special_user = $data['special_uid'];
    $ged_amount = (int) $data['amount'];

    $ged_transactions_storage = $this->entityTypeManager->getStorage('ged_transaction');
    // TODO:: 1369 არი ფულადი კონტრიბუცია.
    // სავარაუდოდ ახალს დაამატებენ და id შეცვალე
    // /admin/structure/taxonomy/manage/transaction_type/overview
    // აქედან ამატებ.
    $transaction_type_id = $this->entityTypeManager->getStorage('taxonomy_term')->load(1369);
    $transaction_type_id2 = $this->entityTypeManager->getStorage('taxonomy_term')->load(1370);

    // TODO:: Description, title და name ჯგერეს კითხე რა უნდა რო ეწეროს.
    $new_transaction = $ged_transactions_storage->create([
      'user_id' => "1",
      'user' => $uid,
      'ged_amount' => $ged_amount,
      'Description' => "ზურაბ გირჩი ჯაფარიძემ გადაურიცხა",
      'title' => 'Japara',
      'name' => 'Japara',
      'transaction_type' => $transaction_type_id,
      'status' => TRUE,
    ]);
    $new_transaction->save();

    // Notify user.
    $getUserInfo = $this->userInfo->getUserInfo(14);
    $text = "${getUserInfo['full_name']}-მ გადმოგირიცხათ ${ged_amount} ჯედი.";
    $text_en = "${getUserInfo['full_name']} has transferred you ${ged_amount} GED.";
    $type = NotificationConstants::DONATION;
    $type_en = NotificationConstants::DONATION_EN;
    $this->notifyUser->notifyUser($uid, $getUserInfo, $type, $type_en, $text, $text_en);

    // TODO:: Description, title და name ჯგერეს კითხე რა უნდა რო ეწეროს.
    $return_transaction = $ged_transactions_storage->create([
      'user_id' => "1",
      'user' => $special_user,
      'ged_amount' => -$ged_amount,
      'Description' => "უკუგატარება",
      'title' => 'Japara',
      'name' => 'Japara',
      'transaction_type' => $transaction_type_id2,
      'status' => TRUE,
    ]);
    $return_transaction->save();

  }

}
