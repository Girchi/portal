<?php

namespace Drupal\girchi_paypal\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Session\AccountProxy;
use Drupal\girchi_donations\Event\DonationEvents;
use Drupal\girchi_donations\Event\DonationEventsConstants;
use Drupal\girchi_donations\Utils\CreateGedTransaction;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PayPal api controller.
 */
class PaypalController extends ControllerBase {
  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager*/
  protected $entityTypeManager;

  /**
   * LoggerChannelInterface.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface*/
  protected $loggerChannelFactory;

  /**
   * AccountProxy for current user.
   *
   * @var \Drupal\Core\Session\AccountProxy*/
  protected $currentUser;

  /**
   * CreateGedTransaction.
   *
   * @var \Drupal\girchi_donations\Utils\CreateGedTransaction*/
  protected $createGedTransaction;

  /**
   * EventDispatcherInterface.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

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
   */
  public function __construct(EntityTypeManager $entityTypeManager,
                              LoggerChannelFactory $loggerChannelFactory,
                              AccountProxy $currentUser,
                              CreateGedTransaction $createGedTransaction,
                              EventDispatcherInterface $dispatcher) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerChannelFactory = $loggerChannelFactory->get('girchi_paypal');
    $this->currentUser = $currentUser;
    $this->createGedTransaction = $createGedTransaction;
    $this->dispatcher = $dispatcher;
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
      $container->get('event_dispatcher')
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
    $node_storage = $this->entityTypeManager->getStorage('donation');

    $client = PayPalClient::client();
    $order_id = $data['order_id'];
    $this->loggerChannelFactory->info($order_id);
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
      $values['status'] = 'OK';
      $values['trans_id'] = $transaction_id;
      $values['amount'] = $amount;

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
      return Response::create("Success");
    }
    catch (\Exception $exception) {

      $this->loggerChannelFactory->error($exception);
      return Response::create($exception);
    }
    return Response::create("Fail");
  }

}
