<?php

namespace Drupal\om_tbc_payments\Services;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\KeyValueStore\KeyValueFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\language\ConfigurableLanguageManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use WeAreDe\TbcPay\TbcPayProcessor;

/**
 * Service for TBC Payments.
 */
class PaymentService {
  /**
   * EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * LoggerFactory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;


  /**
   * Language.
   *
   * @var \Drupal\language\ConfigurableLanguageManager
   */
  protected $languageManager;


  /**
   * Language.
   *
   * @var \WeAreDe\TbcPay\TbcPayProcessor
   */
  protected $tbcPayProcessor;

  /**
   * Request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * FileSystem.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * KeyValue.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactory
   */
  protected $keyValue;

  /**
   * Current User
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Constructor for service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity Type Manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger.
   * @param \Drupal\language\ConfigurableLanguageManager $languageManager
   *   LanguageManager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request.
   * @param \Drupal\Core\File\FileSystem $fileSystem
   *   FileSystem.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactory
   *   KeyValue storage.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   CurrentUser
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              LoggerChannelFactoryInterface $loggerFactory,
                              ConfigurableLanguageManager $languageManager,
                              RequestStack $request_stack,
                              FileSystem $fileSystem,
                              KeyValueFactory $keyValue,
                              AccountProxy $currentUser
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $loggerFactory;
    $this->languageManager = $languageManager;
    $this->request = $request_stack->getCurrentRequest();
    $this->fileSystem = $fileSystem;
    $this->keyValue = $keyValue;
    $this->currentUser = $currentUser;
    $this->tbcPayProcessor = new TbcPayProcessor(
      'Cert',
      'Certpass',
      $this->request->getClientIp()
    );
  }

  /**
   * @param $trans_id
   * @param $payment_data
   *
   * @return bool
   */
  public function addPaymentRecord($trans_id, $payment_data) {
    if (!$trans_id && strlen($trans_id) !== 28) {
      $this->loggerFactory
        ->get('om_tbc_payments')
        ->error('Failed to save payment.');

      return FALSE;
    }
    else {
      try {
        $values = [
          'trans_id' => $trans_id,
          'user_id' => $this->currentUser->id(),
          'amount' => $payment_data['amount'],
          'ip_address' => $this->request->getClientIp(),
          'currency_code' => 981,
          'description' => $payment_data['description'],
        ];
        $payment = $this->entityTypeManager
          ->getStorage('payment')
          ->create($values);
        $payment->save();
        $this->loggerFactory->get('om_tbc_payments')->info('Payment was saved with status INITIAL.');

        return TRUE;
      }
      catch (InvalidPluginDefinitionException $e) {

        $this->loggerFactory
          ->get('om_tbc_payments')
          ->error($e->getMessage());

      }
      catch (PluginNotFoundException $e) {

        $this->loggerFactory
          ->get('om_tbc_payments')
          ->error($e->getMessage());

      }
      catch (EntityStorageException $e) {

        $this->loggerFactory
          ->get('om_tbc_payments')
          ->error($e->getMessage());

      }
    }

    return FALSE;
  }

  /**
   * Wrapper function to create transaction ID.
   *
   * @param int $amount
   *   Amount of GEL.
   * @param string $description
   *   Description for transaction.
   *
   * @return string
   *   String with transaction id.
   */
  public function generateTransactionId($amount, $description) {

    if ($this->languageManager->getCurrentLanguage()->getId() === 'ka') {
      $lang = "GE";
    }
    else {
      $lang = "EN";
    }

    $this->tbcPayProcessor->amount = $amount;
    $this->tbcPayProcessor->currency = 981;
    $this->tbcPayProcessor->description = $description;
    $this->tbcPayProcessor->language = 'GE';
    $id = $this->tbcPayProcessor->sms_start_transaction()['TRANSACTION_ID'];
    if ($id) {
      $this->loggerFactory->get('om_tbc_payments')->info('Transaction ID was generated.');
      $this->addPaymentRecord($id, ['amount' => $amount, 'description' => $description]);
      return $id;
    }
    else {
      $this->loggerFactory->get('om_tbc_payments')->error('Error generating transaction ID in payment service.');
      return NULL;
    }
  }

  /**
   * Make redirect to ufc.
   *
   * @param String $id
   *  TransactionID
   *
   * @return mixed
   *   Redirect.
   */
  public function makePayment($id) {
    try {
//      $id = $this->generateTransactionId($amount, $description);

      if (!$id || strlen($id) !== 28) {
        $this->loggerFactory->get('om_tbc_payments')->error('Error creating transaction ID.');
        return new Response('Transaction ID is missing', Response::HTTP_BAD_REQUEST);
      }
      $key = $this->_getString();
      $this->keyValue->get('om_tbc_payments')->set($key, $id);
      $redirect = new RedirectResponse("/donate/prepare?key=$key");
      $redirect->send();

    }
    catch (\Exception $e) {
      $this->loggerFactory->get('om_tbc_payments')->error($e->getMessage());
    }

    return FALSE;
  }

  /**
   * Function for close day for TBC.
   */
  public function closeDay() {
    $response = $this->tbcPayProcessor->close_day();
    return Json::encode($response);
  }

  /**
   * Function for getting result of payment.
   *
   * @param string $id
   *   Transaction ID.
   * @return array   Result.
   */
  public function getPaymentResult($id) {
    try{
      return $result = $this->tbcPayProcessor->get_transaction_result($id);
    }catch(\Exception $e){
      $this->loggerFactory->get('om_tbc_payments')->error("Can't get result of payment.");
    }

    return [];
  }

  /**
   * @return string
   */
  private function _getString() {
    return uniqid();
  }

}
