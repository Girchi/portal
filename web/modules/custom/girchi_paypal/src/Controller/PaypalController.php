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
use Drupal\girchi_notifications\NotifyDonationService;
use Drupal\girchi_paypal\Utils\PayPalClient;
use Drupal\girchi_paypal\Utils\PaypalUtils;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
   * PaypalUtils.
   *
   * @var \Drupal\girchi_paypal\Utils\PaypalUtils
   */
  private $paypalUtils;
  /**
   * Paypal Client.
   *
   * @var \Drupal\girchi_paypal\Utils\PayPalClient
   */
  private $payPalClient;

  /**
   * NotifyDonationService.
   *
   * @var \Drupal\girchi_notifications\NotifyDonationService
   */
  protected $notifyDonationService;

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
   * @param \Drupal\girchi_paypal\Utils\PaypalUtils $paypalUtils
   *   PaypalUtils.
   * @param \Drupal\girchi_paypal\Utils\PayPalClient $payPalClient
   *   PaypalClient.
   * @param \Drupal\girchi_notifications\NotifyDonationService $notifyDonationService
   *   NotifyDonationService.
   */
  public function __construct(EntityTypeManager $entityTypeManager,
                              LoggerChannelFactory $loggerChannelFactory,
                              AccountProxy $currentUser,
                              CreateGedTransaction $createGedTransaction,
                              EventDispatcherInterface $dispatcher,
                              KeyValueFactory $keyValue,
                              PaypalUtils $paypalUtils,
                              PayPalClient $payPalClient,
                              NotifyDonationService $notifyDonationService
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerChannelFactory = $loggerChannelFactory->get('girchi_paypal');
    $this->currentUser = $currentUser;
    $this->createGedTransaction = $createGedTransaction;
    $this->dispatcher = $dispatcher;
    $this->keyValue = $keyValue->get('girchi_donations');
    $this->paypalUtils = $paypalUtils;
    $this->payPalClient = $payPalClient;
    $this->notifyDonationService = $notifyDonationService;
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
      $container->get('keyvalue'),
      $container->get('girchi_paypal.paypal_utils'),
      $container->get('girchi_paypal.paypal_client'),
      $container->get('girchi_notifications.get_assigned_aim_user')
    );
  }

  /**
   * Create donation and ged transaction after paypal payment.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return mixed
   *   Reponse
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function finishDonation(Request $request) {
    $data = Json::decode($request->getContent());
    try {
      $node_storage = $this->entityTypeManager->getStorage('donation');
      /** @var \PayPalCheckoutSdk\Core\PayPalEnvironment $client */
      $client = $this->payPalClient->client();
      $order_id = $data['order_id'];
      if (!empty($order_id)) {
        $values = [
          'type' => 'donation',
          'title' => 'Donation',
          'user_id' => $this->currentUser->id(),
          'field_source' => 'paypal',
        ];
        $response = $client->execute(new OrdersGetRequest($order_id));
        if ($response->statusCode == 200) {
          if ($response->result->status === 'COMPLETED') {
            $transaction_id = $response->result->id;
            // $first_name = $response->result->payer->name->given_name;
            // $last_name = $response->result->payer->name->surname;
            $amount = $response->result->purchase_units[0]->amount->value;
            $usd = $this->keyValue->get('usd');
            $aim_id = $data['aim'];
            $politician_id = $data['politician'];
            if ($aim_id != NULL) {
              if ($this->paypalUtils->checkDonationAim($aim_id)) {
                $values['aim_donation'] = TRUE;
                $values['aim_id'] = $aim_id;
                $values['status'] = 'OK';
              }
              else {
                $values['status'] = 'INVALID';
              }
            }
            elseif ($politician_id != NULL) {
              if ($this->paypalUtils->checkPolitcian($politician_id)) {
                $values['politician_donation'] = TRUE;
                $values['politician_id'] = $politician_id;
                $values['status'] = 'OK';
              }
              else {
                $values['status'] = 'INVALID';
              }
            }
            $amount = $amount * $usd;
            $values['trans_id'] = $transaction_id;
            $values['amount'] = $amount;
            if ($this->currentUser->id() !== '0' && $values['status'] == 'OK') {
              $ged_trans_id = $this->createGedTransaction->createGedTransaction($this->currentUser->id(), $amount);
              $values['field_ged_transaction'] = $ged_trans_id;
            }

            else {
              $this->loggerChannelFactory->info('Transaction from paypal was failed with transaction id: ');
            }

          }
          else {
            $values['status'] = 'FAILED';
            $this->loggerChannelFactory->info('Failed to connect to paypal server.');
          }
          $donation = $node_storage->create($values);
          $donation->save();
          if ($response->result->status === 'COMPLETED') {
            /** @var \Drupal\girchi_donations\Entity\Donation $donation */
            $donationEvent = new DonationEvents($donation);
            $this->dispatcher->dispatch(DonationEventsConstants::DONATION_SUCCESS, $donationEvent);
            $this->notifyDonationService->notifyDonation($donation);
          }
          $this->loggerChannelFactory->info('Donation from paypal was created with status: ' . $donation->getStatus());
        }
        else {
          $this->loggerChannelFactory->error("Invalid transaction ID");
        }
      }
    }
    catch (\Exception $e) {
      $this->loggerChannelFactory->error($e->getMessage());
    }
    return [
      '#type' => 'markup',
      '#theme' => 'girchi_donations_denied',
    ];
  }

  /**
   * Page that will load after donation finish.
   */
  public function donationPage(Request $request) {
    $trans_id = $request->query->get('trans_id');
    $token = $this->keyValue->get($trans_id);
    if (!empty($trans_id) && !$token) {
      $donation_storage = $this->entityTypeManager->getStorage('donation');
      /** @var \Drupal\girchi_donations\Entity\Donation $donation */
      $donation = $donation_storage->loadByProperties(['trans_id' => $trans_id]);
      $donation = reset($donation);

      if ($donation) {
        $user_id = $donation->getUser()->id();
        if ($user_id == $this->currentUser->id()) {
          $status = $donation->getStatus();
          /** @var \Drupal\girchi_ged_transactions\Entity\GedTransaction $gedTransaction */
          $ged_transaction = $donation->get('field_ged_transaction');
          if ($ged_transaction && $ged_transaction->entity) {
            $amount = $ged_transaction->entity->get('ged_amount')->value;
          }
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
            '#amount' => $amount ?? '',
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
