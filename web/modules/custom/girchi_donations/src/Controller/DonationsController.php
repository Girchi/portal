<?php

namespace Drupal\girchi_donations\Controller;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\KeyValueStore\KeyValueFactory;
use Drupal\Core\Session\AccountProxy;
use Drupal\girchi_donations\Event\DonationEvents;
use Drupal\girchi_donations\Event\DonationEventsConstants;
use Drupal\girchi_donations\Utils\DonationUtils;
use Drupal\girchi_donations\Utils\GedCalculator;
use Drupal\om_tbc_payments\Services\PaymentService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DonationsController.
 */
class DonationsController extends ControllerBase {

  /**
   * ConfigFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Payment service.
   *
   * @var \Drupal\om_tbc_payments\Services\PaymentService
   */
  protected $omediaPayment;

  /**
   * Ged calculator.
   *
   * @var \Drupal\girchi_donations\Utils\GedCalculator
   */
  public $gedCalculator;

  /**
   * KeyValue.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactory
   */

  protected $keyValue;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;


  /**
   * Drupal\Core\Form\FormBuilder definition.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * DonationUtils definition.
   *
   * @var \Drupal\girchi_donations\Utils\DonationUtils
   */
  protected $donationUtils;


  /**
   * Event Dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * Construct.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   ConfigFactory.
   * @param \Drupal\om_tbc_payments\Services\PaymentService $omediaPayment
   *   Payments.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   ET manager.
   * @param \Drupal\girchi_donations\Utils\GedCalculator $gedCalculator
   *   GedCalculator.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactory $keyValue
   *   KeyValue storage.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   AccountProxy for current user.
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   FormBuilder.
   * @param \Drupal\girchi_donations\Utils\DonationUtils $donationUtils
   *   Donation utils.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   EventDispatcher.
   */
  public function __construct(ConfigFactory $configFactory,
                              PaymentService $omediaPayment,
                              EntityTypeManager $entityTypeManager,
                              GedCalculator $gedCalculator,
                              KeyValueFactory $keyValue,
                              AccountProxy $currentUser,
                              FormBuilder $formBuilder,
                              DonationUtils $donationUtils,
                              EventDispatcherInterface $dispatcher
  ) {
    $this->configFactory = $configFactory;
    $this->omediaPayment = $omediaPayment;
    $this->entityTypeManager = $entityTypeManager;
    $this->gedCalculator = $gedCalculator;
    $this->keyValue = $keyValue;
    $this->currentUser = $currentUser;
    $this->formBuilder = $formBuilder;
    $this->donationUtils = $donationUtils;
    $this->dispatcher = $dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('om_tbc_payments.payment_service'),
      $container->get('entity_type.manager'),
      $container->get('girchi_donations.ged_calculator'),
      $container->get('keyvalue'),
      $container->get('current_user'),
      $container->get('form_builder'),
      $container->get('girchi_donations.donation_utils'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Index.
   *
   * @return array
   *   Return array with template and variables
   */
  public function index() {
    $config = $this->configFactory->get('om_site_settings.site_settings');
    $right_block = $config->get('donation_right_block')['value'];
    $form_single = $this->formBuilder()
      ->getForm("Drupal\girchi_donations\Form\SingleDonationForm");
    $form_multiple = $this->formBuilder()
      ->getForm("Drupal\girchi_donations\Form\MultipleDonationForm");

    return [
      '#type' => 'markup',
      '#theme' => 'girchi_donations',
      '#form_single' => $form_single,
      '#form_multiple' => $form_multiple,
      '#right_block' => $right_block,
    ];
  }

  /**
   * Route for final destination of donation.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return mixed
   *   Response
   */
  public function finishDonation(Request $request) {
    try {
      $params = $request->request;
      $trans_id = $params->get('trans_id');
      $storage = $this->entityTypeManager()->getStorage('donation');
      $reg_donation_storage = $this->entityTypeManager()
        ->getStorage('regular_donation');

      $ged_manager = $this->entityTypeManager()->getStorage('ged_transaction');

      if (!$trans_id) {
        $this->getLogger('girchi_donations')->error('Trans ID is missing.');
        return new JsonResponse('Transaction ID is missing', Response::HTTP_BAD_REQUEST);
      }
      $donations = $storage->loadByProperties(['trans_id' => $trans_id]);
      $reg_donations = $reg_donation_storage->loadByProperties(['trans_id' => $trans_id]);

      if (empty($donations) && empty($reg_donations)) {
        $this->getLogger('girchi_donations')
          ->error('Donation or Regular Donation entity not found.');
        return new JsonResponse('Donation or Regular Donation entity not found.', Response::HTTP_BAD_REQUEST);
      }
      /** @var \Drupal\girchi_donations\Entity\Donation $donation */
      $donation = reset($donations);
      /** @var \Drupal\girchi_donations\Entity\RegularDonation $reg_donation */
      $reg_donation = reset($reg_donations);

      $transaction_type_id = $this->entityTypeManager()->getStorage('taxonomy_term')->load(1369) ? '1369' : NULL;

      $result = $this->omediaPayment->getPaymentResult($trans_id);
      if ($result['RESULT_CODE'] === "000") {
        if ($donation) {
          if ($donation->getStatus() !== 'OK') {
            /** @var \Drupal\user\Entity\User $user */
            $user = $donation->getUser();
            $donation->setStatus('OK');
            $this->getLogger('girchi_donations')
              ->info("Status was Updated to OK, ID:$trans_id.");
            $gel_amount = $donation->getAmount();
            $ged_amount = $this->gedCalculator->calculate($gel_amount);
            if ($user->id() !== '0') {
              $transaction = $ged_manager->create([
                'user_id' => "1",
                'user' => $user->id(),
                'ged_amount' => $ged_amount,
                'title' => 'Donation',
                'name' => 'Donation',
                'status' => TRUE,
                'Description' => 'Transaction was created by donation',
                'transaction_type' => $transaction_type_id,
              ]);
              $transaction->save();
              $donation->set('field_ged_transaction', $transaction->id());
              $donation->save();
              $auth = TRUE;
            }
            else {
              $auth = FALSE;
            }
            $donationEvent = new DonationEvents($donation);
            $this->dispatcher->dispatch(DonationEventsConstants::DONATION_SUCCESS, $donationEvent);
            $this->getLogger('girchi_donations')
              ->info("Ged transaction was made.");
            $this->getLogger('girchi_donations')
              ->info("Payment was successful, ID:$trans_id.");
            return [
              '#type' => 'markup',
              '#theme' => 'girchi_donations_success',
              '#amount' => $ged_amount,
              '#auth' => $auth,
            ];
          }
          else {
            return $this->redirect('user.page');
          }
        }
        elseif ($reg_donation) {
          if ($reg_donation->getStatus() !== 'ACTIVE') {
            /** @var \Drupal\user\Entity\User $user */
            $reg_donation->setStatus('ACTIVE');
            $ged_amount = $this->gedCalculator->calculate($reg_donation->get('amount')->value);
            $transaction = $ged_manager->create([
              'user_id' => "1",
              'user' => $reg_donation->getOwnerId(),
              'ged_amount' => $ged_amount,
              'title' => 'Donation',
              'name' => 'Donation',
              'status' => TRUE,
              'Description' => 'Transaction was created by donation',
              'transaction_type' => $transaction_type_id,
            ]);
            $transaction->save();
            $this->getLogger('girchi_donations')
              ->info("Ged transaction was made.");
            $type = $reg_donation->get('type')->value;
            $entity_id = $reg_donation->get('aim_id')->target_id ? $reg_donation->get('aim_id')->target_id : $reg_donation->get('politician_id')->target_id;
            $donation = $this->donationUtils->addDonationRecord($type, [
              'trans_id' => $reg_donation->get('trans_id')->value,
              'amount' => (int) $reg_donation->get('amount')->value,
              'user_id' => $reg_donation->getOwnerId(),
              'field_donation_type' => 1,
              'field_ged_transaction' => $transaction->id(),
              'status' => 'OK',
            ], $entity_id);
            /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $donations */
            $donations = $reg_donation->get('field_donations');
            $donations->appendItem($donation->id());
            $this->getLogger('girchi_donations')->info("Donation was made.");
            $reg_donation->save();
            $donationEvent = new DonationEvents($donation);
            $this->dispatcher->dispatch(DonationEventsConstants::DONATION_SUCCESS, $donationEvent);
            $this->getLogger('girchi_donations')
              ->info('Regular donation was activated.');
            $reg_donation_details = [
              'frequency' => $reg_donation->get('frequency')->value,
              'day' => $reg_donation->get('payment_day')->value,
              'date' => $reg_donation->get('next_payment_date')->value,
            ];
            return [
              '#type' => 'markup',
              '#theme' => 'girchi_donations_success',
              '#regular_donation' => TRUE,
              '#reg_data' => $reg_donation_details,
            ];
          }
          else {
            return $this->redirect('user.page');
          }
        }

      }
      else {
        $code = $result['RESULT_CODE'];
        if ($donation) {
          $donation->setStatus('FAILED');
          $donation->save();
          $this->getLogger('girchi_donations')
            ->error("Donation failed code:$code, ID:$trans_id.");
          return [
            '#type' => 'markup',
            '#theme' => 'girchi_donations_fail',
          ];
        }
        elseif ($reg_donation) {
          $reg_donation->setStatus('FAILED');
          $reg_donation->save();
          $this->getLogger('girchi_donations')
            ->error("Regular Donation failed code:$code, ID:$trans_id.");
          return [
            '#type' => 'markup',
            '#theme' => 'girchi_donations_fail',
            '#regular_donation' => TRUE,
          ];
        }

      }
    }
    catch (\Exception $e) {
      $this->getLogger($e->getMessage());
    }

    $this->getLogger('girchi_donations')
      ->error('Trans ID or Donation is missing.');
    return new JsonResponse('Transaction ID is missing', Response::HTTP_BAD_REQUEST);
  }

  /**
   * Route for donation technical failure.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return mixed
   *   mixed
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function failDonation(Request $request) {
    $params = $request->request;
    $trans_id = $params->get('trans_id');
    if (!$trans_id) {
      $this->getLogger('girchi_donations')->error("Trans ID is missing.");
      return new JsonResponse('Transaction ID is missing', Response::HTTP_BAD_REQUEST);
    }

    $result = $this->omediaPayment->getPaymentResult($trans_id);
    $code = $result['RESULT_CODE'];
    $storage = $this->entityTypeManager()->getStorage('donation');
    /** @var \Drupal\girchi_donations\Entity\Donation $donation */
    $donation = $storage->loadByProperties(['trans_id' => $trans_id]);
    $donation->setStatus('FAILED');
    $this->getLogger('girchi_donations')
      ->error("Payment failed code:$code,  ID:$trans_id.");
    return [
      '#type' => 'markup',
      '#theme' => 'girchi_donations_fail',
    ];
  }

  /**
   * Route for regular donations page.
   *
   * @return mixed
   *   mixed
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function regularDonations() {
    $regular_donation_storage = $this->entityTypeManager->getStorage('regular_donation');
    $regular_donations = $regular_donation_storage->getQuery()
      ->condition('user_id', $this->currentUser->id(), '=')
      ->condition('status', ['ACTIVE', 'PAUSED'], 'IN')
      ->sort('created', 'DESC')
      ->execute();
    $regular_donations = $regular_donation_storage->loadMultiple($regular_donations);
    $regular_donation_form = $this->formBuilder->getForm('Drupal\girchi_donations\Form\MultipleDonationForm');
    $politicans = $this->donationUtils->getPoliticians();
    $terms = $this->donationUtils->getTerms();
    $language_code = $this->languageManager()->getCurrentLanguage()->getId();

    return [
      '#type' => 'markup',
      '#theme' => 'regular_donations',
      '#regular_donations' => $regular_donations,
      '#regular_donation_form' => $regular_donation_form,
      '#politicians' => $politicans,
      '#terms' => $terms,
      '#language' => $language_code,
      '#current_user_id' => $this->currentUser->id(),
    ];
  }

  /**
   * Route for changing donation status.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function updateDonationStatus(Request $request) {
    try {
      $user_id = $request->request->get('user_id');
      if ($user_id == $this->currentUser->id()) {
        $action = $request->request->get('action');
        $donation_id = $request->request->get('id');
        /** @var \Drupal\Core\Entity\EntityStorageBase $donation_storage */
        $regular_donation_storage = $this->entityTypeManager->getStorage('regular_donation');
        /** @var \Drupal\girchi_donations\Entity\RegularDonation $regular_donation */
        $regular_donation_id = $regular_donation_storage->getQuery()
          ->condition('id', $donation_id, '=')
          ->condition('status', ['ACTIVE', 'PAUSED'], 'IN')
          ->execute();
        $regular_donation = $regular_donation_storage->load(key($regular_donation_id));
        if ($action == "pause") {
          $regular_donation->setStatus('PAUSED');
          $regular_donation->save();
        }
        elseif ($action == "resume") {
          $regular_donation->setStatus('ACTIVE');
          $regular_donation->save();
        }
        return new JsonResponse([
          "statusCode" => 200,
          "message" => "Donation status has been changed to " . $action,
        ]);
      }
      else {
        return new JsonResponse([
          "statusCode" => 400,
          "message" => "Failed to change donation status.",
        ]);
      }
    }
    catch (\Exception $e) {
      $this->getLogger('girchi_donations')->error($e->getMessage());
    }

  }

  /**
   * Route for editing donation.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Symfony request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function editDonation(Request $request) {
    try {
      $user_id = $request->request->get('user-id');
      if ($user_id == $this->currentUser->id()) {
        $donation_id = $request->request->get('donation-id');
        $amount = $request->request->get('amount');
        $period = $request->request->get('period');
        $aim = $request->request->get('aim') ? $request->request->get('aim') : "";
        $politician = $request->request->get('politician') ? $request->request->get('politician') : "";
        $date = $request->request->get('date');

        if (!empty($donation_id) &&
          !empty($amount) &&
          !empty($period) &&
          !empty($date)) {
          if (in_array($period, [1, 3, 6])
            && in_array($date, range(1, 28))
          ) {
            /** @var \Drupal\Core\Entity\EntityStorageBase $donation_storage */
            $donation_storage = $this->entityTypeManager()
              ->getStorage('regular_donation');
            /** @var \Drupal\girchi_donations\Entity\RegularDonation $regular_donation */
            $regular_donation = $donation_storage->loadByProperties(['id' => $donation_id]);
            $regular_donation[$donation_id]->set('amount', $amount);
            $regular_donation[$donation_id]->set('frequency', $period);
            $regular_donation[$donation_id]->set('payment_day', $date);

            if (!empty($aim)) {
              $regular_donation[$donation_id]->set('aim_id', $aim);
            }
            elseif (!empty($politician)) {
              $regular_donation[$donation_id]->set('politician_id', $politician);
            }
            $regular_donation[$donation_id]->save();
            $this->messenger()
              ->addMessage($this->t('Donation has been changed.'));
          }
          else {
            $this->messenger()
              ->addError($this->t('Failed to change Donation.'));
          }
        }
      }
      else {
        $this->messenger()
          ->addError($this->t('You are not authorized to change donation.'));
      }
      return $this->redirect('girchi_donations.regular_donations');

    }
    catch (\Exception $e) {
      $this->getLogger('girchi_donations')->error($e->getMessage());
    }

  }

}
