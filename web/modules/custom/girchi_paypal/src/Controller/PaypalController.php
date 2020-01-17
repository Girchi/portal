<?php

namespace Drupal\girchi_paypal\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\KeyValueStore\KeyValueFactory;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Session\AccountProxy;
use Drupal\girchi_donations\Event\DonationEvents;
use Drupal\girchi_donations\Event\DonationEventsConstants;
use Drupal\girchi_donations\Utils\CreateGedTransaction;
use Drupal\girchi_paypal\Utils\PayPalClient;
use mysql_xdevapi\Exception;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * PayPal api controller.
 */
class PaypalController extends ControllerBase {
  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * LoggerChannelInterface.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $loggerChannelFactory;

  /**
   * AccountProxy for current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * CreateGedTransaction.
   *
   * @var \Drupal\girchi_donations\Utils\CreateGedTransaction
   */
  protected $createGedTransaction;

  /**
   * EventDispatcherInterface.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;
  /**
   * KeyValueFactory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactory
   */
  protected $keyValue;

  /**
   * PaypalController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   EntityTypeManager.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerChannelFactory
   *   LoggerChannelFactory.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   AccountProxy.
   * @param \Drupal\girchi_donations\Utils\CreateGedTransaction $createGedTransaction
   *   CreateGedTransaction.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   EventDispatcherInteface.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactory $keyValue
   *   KeyValue.
   */
  public function __construct(EntityTypeManager $entityTypeManager,
                              LoggerChannelFactory $loggerChannelFactory,
                              AccountProxy $currentUser,
                              CreateGedTransaction $createGedTransaction,
                              EventDispatcherInterface $dispatcher,
                              KeyValueFactory $keyValue
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerChannelFactory = $loggerChannelFactory->get('girchi_paypal');
    $this->currentUser = $currentUser;
    $this->createGedTransaction = $createGedTransaction;
    $this->dispatcher = $dispatcher;
    $this->keyValue = $keyValue->get('girchi_donations');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $container->get('current_user'),
      $container->get('girchi_donations.create_ged_transaction'),
      $container->get('event_dispatcher'),
      $container->get('keyvalue')
    );
  }

  /**
   * Create donation and ged transaction after paypal payment.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Reponse
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function finishDonation(Request $request) {
    $data = Json::decode($request->getContent());
    try {
      $node_storage = $this->entityTypeManager->getStorage('donation');

      $client = PayPalClient::client();
      $order_id = $data['order_id'];
      if (!empty($order_id)) {
        $aim_id = $data['aim'];
        $politician_id = $data['politician'];
        $response = $client->execute(new OrdersGetRequest($order_id));
        $values = [
          'type' => 'donation',
          'title' => "Donation",
          'user_id' => $this->currentUser->id(),
        ];

        if ($aim_id != NULL) {
          $values['aim_donation'] = TRUE;
          $values['aim_id'] = $aim_id;
        }
        elseif ($politician_id != NULL) {
          $values['politician_donation'] = TRUE;
          $values['politician_id'] = $politician_id;
        }

        if ($response->statusCode === 200) {
          $transaction_id = $response->result->id;
          // $first_name = $response->result->payer->name->given_name;
          // $last_name = $response->result->payer->name->surname;
          $amount = $response->result->purchase_units[0]->amount->value;
          $usd = $this->keyValue->get('usd');
          $amount = $amount * $usd;
          $values['status'] = 'OK';
          $values['trans_id'] = $transaction_id;
          $values['amount'] = $amount;

          /** @var \Drupal\girchi_donations\Entity\Donation $donation */
          $donation = $node_storage->create($values);

          if ($this->currentUser->id() !== '0') {
            $ged_trans_id = $this->createGedTransaction->createGedTransaction($donation);
            $donationEvent = new DonationEvents($donation);
            $this->dispatcher->dispatch(DonationEventsConstants::DONATION_SUCCESS, $donationEvent);
            $donation->set('field_ged_transaction', $ged_trans_id);
          }
        }
        else {
          $values['status'] = 'FAILED';
          $donation = $node_storage->create($values);
        }

        try {
          $donation->save();
          $this->loggerChannelFactory->info('Donation from paypal was created with trans_id: ' . $donation->get('trans_id')->value);
          return JsonResponse::create("Successfully created donation from paypal.");
        }
        catch (\Exception $e) {

          $this->loggerChannelFactory->error($e->getMessage());
          return JsonResponse::create('Failed to create donation from paypal.');
        }
      }
      else {
        $this->loggerChannelFactory->error("Invalid transaction ID");
        return JsonResponse::create('Invalid transaction ID');
      }
    }
    catch (Exception $e) {
      $this->loggerChannelFactory->error($e->getMessage());
    }
  }

  /**
   * Page that will load after donation finish.
   */
  public function donationPage(Request $request) {
    $trans_id = $request->query->get('trans_id');
    $token = $this->keyValue->get($trans_id);
    if (!empty($trans_id) && !isset($token)) {
      $donation_storage = $this->entityTypeManager->getStorage('donation');
      /** @var \Drupal\girchi_donations\Entity\Donation $donation */
      $donation = $donation_storage->loadByProperties(['trans_id' => $trans_id]);
      $donation = reset($donation);

      if ($donation) {
        $user_id = $donation->getUser()->id();
        if ($user_id == $this->currentUser->id()) {
          $status = $donation->getStatus();
          /** @var \Drupal\girchi_ged_transactions\Entity\GedTransaction $gedTransaction */
          $gedTransaction = $donation->get('field_ged_transaction');
          $amount = $gedTransaction->entity->get('ged_amount')->value;
          if ($user_id == 0) {
            $auth = FALSE;
          }
          else {
            $auth = TRUE;
          }
          $this->keyValue->set($trans_id, $trans_id);
          return [
            '#type' => 'markup',
            '#theme' => 'girchi_donations_paypal',
            '#status' => $status,
            '#amount' => $amount,
            '#auth' => $auth,
          ];
        }
      }
      else {
        return [
          '#type' => 'markup',
          '#theme' => 'girchi_donations_denied',
        ];
      }
    }
    else {
      return [
        '#type' => 'markup',
        '#theme' => 'girchi_donations_denied',
      ];
    }
  }

}
