<?php

namespace Drupal\girchi_donations\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilder;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\KeyValueStore\KeyValueFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\girchi_banking\Services\BankingUtils;
use Drupal\girchi_donations\Entity\RegularDonation;
use Drupal\girchi_donations\Event\DonationEvents;
use Drupal\girchi_donations\Event\DonationEventsConstants;
use Drupal\girchi_donations\Utils\DonationUtils;
use Drupal\girchi_donations\Utils\GedCalculator;
use Drupal\girchi_notifications\NotifyDonationService;
use Drupal\girchi_users\UserBadgesChangeDetectionService;
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
   * Banking utils definition.
   *
   * @var \Drupal\girchi_banking\Services\BankingUtils
   */
  protected $bankingUtils;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $accountProxy;

  /**
   * Drupal\Core\Entity\EntityFormBuilder definition.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilder
   */
  protected $entityFormBuilder;

  /**
   * NotifyDonationService.
   *
   * @var \Drupal\girchi_notifications\NotifyDonationService
   */
  protected $notifyDonationService;

  /**
   * UserBadgesChangeDetectionService.
   *
   * @var \Drupal\girchi_users\UserBadgesChangeDetectionService
   */
  protected $userBadgeChangeDetection;

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
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   FormBuilder.
   * @param \Drupal\girchi_donations\Utils\DonationUtils $donationUtils
   *   Donation utils.
   * @param \Drupal\girchi_banking\Services\BankingUtils $bankingUtils
   *   Banking utils.
   * @param \Drupal\Core\Session\AccountProxy $accountProxy
   *   Account proxy.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   EventDispatcher.
   * @param \Drupal\Core\Entity\EntityFormBuilder $entityFormBuilder
   *   Entity form builder.
   * @param \Drupal\girchi_notifications\NotifyDonationService $notifyDonationService
   *   NotifyDonationService.
   * @param \Drupal\girchi_users\UserBadgesChangeDetectionService $userBadgesChangeDetectionService
   *   UserBadgesChangeDetectionService.
   */
  public function __construct(ConfigFactory $configFactory,
                              PaymentService $omediaPayment,
                              EntityTypeManager $entityTypeManager,
                              GedCalculator $gedCalculator,
                              KeyValueFactory $keyValue,
                              FormBuilder $formBuilder,
                              DonationUtils $donationUtils,
                              BankingUtils $bankingUtils,
                              AccountProxy $accountProxy,
                              EventDispatcherInterface $dispatcher,
                              EntityFormBuilder $entityFormBuilder,
                              NotifyDonationService $notifyDonationService,
                              UserBadgesChangeDetectionService $userBadgesChangeDetectionService
  ) {
    $this->configFactory = $configFactory;
    $this->omediaPayment = $omediaPayment;
    $this->entityTypeManager = $entityTypeManager;
    $this->gedCalculator = $gedCalculator;
    $this->keyValue = $keyValue;
    $this->formBuilder = $formBuilder;
    $this->donationUtils = $donationUtils;
    $this->bankingUtils = $bankingUtils;
    $this->accountProxy = $accountProxy;
    $this->dispatcher = $dispatcher;
    $this->entityFormBuilder = $entityFormBuilder;
    $this->notifyDonationService = $notifyDonationService;
    $this->userBadgeChangeDetection = $userBadgesChangeDetectionService;
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
      $container->get('form_builder'),
      $container->get('girchi_donations.donation_utils'),
      $container->get('girchi_banking.utils'),
      $container->get('current_user'),
      $container->get('event_dispatcher'),
      $container->get('entity.form_builder'),
      $container->get('girchi_notifications.get_assigned_aim_user'),
      $container->get('girchi_users.user_badges_change_detection')
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
    $paypal_form = $this->formBuilder()
      ->getForm("Drupal\girchi_donations\Form\PaypalDonationForm");
    $card_save_form = $this->formBuilder()->getForm('Drupal\girchi_banking\Form\SaveCreditCardForm');
    $has_active_card = $this->bankingUtils->hasAvailableCards($this->accountProxy->id());
    $cards = $this->bankingUtils->getActiveCards($this->accountProxy->id());

    // Get politicians.
    $politicians = $this->donationUtils->getPoliticians();
    // Get donation aim.
    $donation_aim = $this->donationUtils->getTerms();

    $aim_or_politicians = array_merge($politicians, $donation_aim);

    return [
      '#type' => 'markup',
      '#theme' => 'girchi_donations',
      '#form_single' => $form_single,
      '#form_multiple' => $form_multiple,
      '#paypal_form' => $paypal_form,
      '#right_block' => $right_block,
      '#has_active_card' => $has_active_card,
      '#card_save_form' => $card_save_form,
      '#cards' => $cards,
      '#aim_or_politicians' => $aim_or_politicians,
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
      $card_storage = $this->entityTypeManager()
        ->getStorage('credit_card');
      $ged_manager = $this->entityTypeManager()->getStorage('ged_transaction');

      if (!$trans_id) {
        $this->getLogger('girchi_donations')->error('Trans ID is missing.');
        return new JsonResponse('Transaction ID is missing', Response::HTTP_BAD_REQUEST);
      }
      $donations = $storage->loadByProperties(['trans_id' => $trans_id]);
      $credit_cards = $card_storage->loadByProperties(['trans_id' => $trans_id]);
      if (empty($donations) && empty($credit_cards)) {
        $this->getLogger('girchi_donations')
          ->error('Donation or Regular Donation entity not found.');
        return new JsonResponse('Donation or Regular Donation entity not found.', Response::HTTP_BAD_REQUEST);
      }
      /** @var \Drupal\girchi_donations\Entity\Donation $donation */
      $donation = reset($donations);
      /** @var \Drupal\girchi_donations\Entity\RegularDonation $reg_donation */
      $credit_card = reset($credit_cards);

      $transaction_type_id = $this->entityTypeManager()
        ->getStorage('taxonomy_term')
        ->load(1369) ? '1369' : NULL;

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
                'user_id' => '1',
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
              $auth = TRUE;
            }
            else {
              $auth = FALSE;
            }
            $donation->save();
            $donationEvent = new DonationEvents($donation);
            $this->dispatcher->dispatch(DonationEventsConstants::DONATION_SUCCESS, $donationEvent);
            $this->notifyDonationService->notifyDonation($donation);
            $this->userBadgeChangeDetection->addDonationBadge($user->id(), TRUE);
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
        elseif ($credit_card) {
          if ($credit_card->getStatus() !== 'ACTIVE') {
            $active_card = $this->bankingUtils->parseAndMerge($credit_card, $result);
            if (!$active_card) {
              return [
                '#type' => 'markup',
                '#theme' => 'girchi_donations_fail',
              ];
            }
            else {
              return [
                '#type' => 'markup',
                '#theme' => 'girchi_donations_success',
                '#card' => $active_card,
              ];
            }
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
        elseif ($credit_card) {
          $credit_card->setStatus('FAILED');
          $credit_card->save();
          return [
            '#type' => 'markup',
            '#theme' => 'girchi_donations_fail',
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
      ->condition('user_id', $this->accountProxy->id(), '=')
      ->condition('status', ['ACTIVE', 'PAUSED'], 'IN')
      ->sort('created', 'DESC')
      ->execute();
    $regular_donations = $regular_donation_storage->loadMultiple($regular_donations);
    $regular_donation_form = $this->formBuilder->getForm('Drupal\girchi_donations\Form\MultipleDonationForm');
    $language_code = $this->languageManager()->getCurrentLanguage()->getId();
    $cards = $this->bankingUtils->getActiveCards($this->accountProxy->id());

    // Get politicians.
    $politicians = $this->donationUtils->getPoliticians();
    // Get donation aim.
    $donation_aim = $this->donationUtils->getTerms();

    $aim_or_politicians = array_merge($politicians, $donation_aim);

    return [
      '#type' => 'markup',
      '#theme' => 'regular_donations',
      '#regular_donations' => $regular_donations,
      '#regular_donation_form' => $regular_donation_form,
      '#language' => $language_code,
      '#current_user_id' => $this->accountProxy->id(),
      '#cards' => $cards,
      '#aim_or_politicians' => $aim_or_politicians,
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
      if ($user_id == $this->accountProxy->id()) {
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
          $this->userBadgeChangeDetection->deleteRegDonationBadge($user_id);
        }
        elseif ($action == "resume") {
          $regular_donation->setStatus('ACTIVE');
          $regular_donation->save();
          $this->userBadgeChangeDetection->addDonationBadge($user_id, FALSE);
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
   * Route for regular donation edit.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   User.
   * @param \Drupal\girchi_donations\Entity\RegularDonation $regular
   *   Regular.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|array
   *   Response.
   */
  public function editRegularDonationAction(AccountInterface $user, RegularDonation $regular) {
    try {
      if ($this->accountProxy->id() == $user->id() && $regular->getOwnerId() == $user->id()) {
        $entity_form = $this->entityFormBuilder->getForm($regular);
        $cards = $this->bankingUtils->getActiveCards($this->accountProxy->id());
        $card_helper = [];
        $card_helper['has_card'] = $regular->get('field_credit_card')->first() ? TRUE : FALSE;
        $card_helper['card_id'] = $card_helper['has_card'] ? $regular->get('field_credit_card')->first()->target_id : NULL;
        $card_helper['ged_amount'] = $this->donationUtils->gedCalculator->calculate($regular->get('amount')->value);
        $current_politician_id = $regular->get('politician_id')->target_id;
        $current_aim_id = $regular->get('aim_id')->target_id;

        // Get politicians.
        $politicians = $this->donationUtils->getPoliticians();
        // Get donation aim.
        $donation_aim = $this->donationUtils->getTerms();

        return [
          '#type' => 'markup',
          '#theme' => 'girchi_donations_regular_edit',
          '#entity' => $regular,
          '#entity_form' => $entity_form,
          '#cards' => $cards,
          '#card_helper' => $card_helper,
          '#politicians' => $politicians,
          '#donation_aim' => $donation_aim,
          '#current_politician_id' => $current_politician_id,
          '#current_aim_id' => $current_aim_id,
        ];
      }
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->getLogger('girchi_donations')->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->getLogger('girchi_donations')->error($e->getMessage());
    }
    catch (MissingDataException $e) {
      $this->getLogger('girchi_donations')->error($e->getMessage());
    }

    $this->messenger()->addError($this->t('Access denied'));
    return $this->redirect('user.page');
  }

}
