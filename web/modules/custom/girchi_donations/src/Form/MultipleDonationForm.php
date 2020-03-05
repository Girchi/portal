<?php

namespace Drupal\girchi_donations\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\girchi_banking\Services\BankingUtils;
use Drupal\girchi_donations\Utils\DonationUtils;
use Drupal\om_tbc_payments\Services\PaymentService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SingleDonationForm.
 */
class MultipleDonationForm extends FormBase {

  /**
   * Utils service.
   *
   * @var \Drupal\girchi_donations\Utils\DonationUtils
   */
  private $donationUtils;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Payment service.
   *
   * @var \Drupal\om_tbc_payments\Services\PaymentService
   */
  protected $omediaPayment;

  /**
   * Politicians.
   *
   * @var array
   */
  protected $politicians;

  /**
   * Options.
   *
   * @var array
   */
  protected $options;

  /**
   * Current currency.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface|mixed
   */
  protected $currency;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $accountProxy;

  /**
   * Banking utils definition.
   *
   * @var \Drupal\girchi_banking\Services\BankingUtils
   */
  protected $bankingUtils;

  /**
   * Constructs a new UserController object.
   *
   * @param \Drupal\girchi_donations\Utils\DonationUtils $donationUtils
   *   Donation Utils.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger.
   * @param \Drupal\om_tbc_payments\Services\PaymentService $omediaPayment
   *   Omedia Payment.
   * @param \Drupal\Core\Session\AccountProxy $accountProxy
   *   Current user.
   * @param \Drupal\girchi_banking\Services\BankingUtils $bankingUtils
   *   Banking utils.
   */
  public function __construct(DonationUtils $donationUtils,
                              MessengerInterface $messenger,
                              PaymentService $omediaPayment,
                              AccountProxy $accountProxy,
                              BankingUtils $bankingUtils) {
    $this->donationUtils = $donationUtils;
    $this->messenger = $messenger;
    $this->omediaPayment = $omediaPayment;
    $this->accountProxy = $accountProxy;
    $this->bankingUtils = $bankingUtils;
    $this->politicians = $donationUtils->getPoliticians();
    $this->options = $donationUtils->getTerms();
    $this->currency = $donationUtils->gedCalculator->getCurrency();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('girchi_donations.donation_utils'),
        $container->get('messenger'),
        $container->get('om_tbc_payments.payment_service'),
        $container->get('current_user'),
        $container->get('girchi_banking.utils')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'multiple_donation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['amount'] = [
      '#type' => 'number',
      '#attributes' => [
        'class' => [
          'form-control form-control-lg',
        ],
        'placeholder' => $this->t('Enter amount of GEL'),
      ],
      '#weight' => '0',
      '#required' => TRUE,
    ];
    $form['frequency'] = [
      '#type' => 'select',
      '#options' => [
        '1' => $this->t('Every month'),
        '3' => $this->t('Once in every 3 months'),
        '6' => $this->t('Once in every 6 months'),
      ],
      '#required' => TRUE,
    ];
    $form['date'] = [
      '#type' => 'select',
      '#options' => [
        '1' => '1',
        '2' => '2',
        '3' => '3',
        '4' => '4',
        '5' => '5',
        '6' => '6',
        '7' => '7',
        '8' => '8',
        '9' => '9',
        '10' => '10',
        '11' => '11',
        '12' => '12',
        '13' => '13',
        '14' => '14',
        '15' => '15',
        '16' => '16',
        '17' => '17',
        '18' => '18',
        '19' => '19',
        '20' => '20',
        '21' => '21',
        '22' => '22',
        '23' => '23',
        '24' => '24',
        '25' => '26',
        '27' => '27',
        '28' => '28',
      ],

    ];
    $form['donation_aim'] = [
      '#type' => 'hidden',
      '#required' => FALSE,
      '#empty_value' => '',
      '#attributes' => [
        'class' => [
          'tbc-multiple-hidden-aim',
        ],
      ],
    ];
    $form['politicians'] = [
      '#type' => 'hidden',
      '#required' => FALSE,
      '#empty_value' => '',
      '#attributes' => [
        'class' => [
          'tbc-multiple-hidden-politician',
        ],
      ],
    ];
    $form['currency'] = [
      '#title' => 'currency',
      '#type' => 'hidden',
      '#attributes' => [
        'id' => [
          'currency_girchi',
        ],
      ],
      '#value' => $this->currency,
    ];
    $form['card_id'] = [
      '#title' => 'card_id',
      '#type' => 'hidden',
      '#attributes' => [
        'id' => [
          'card_id',
        ],
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#attributes' => [
        'class' => [
          'btn btn-lg btn-block btn-success text-uppercase mt-4',
        ],
      ],
      '#value' => $this->t('Donate'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $card_id = $form_state->getValue('card_id');
    if (!$card_id) {
      $form_state->setErrorByName('card_id', $this->t('Please select credit card for regular donation.'));
    }
    else {
      if (!$this->bankingUtils->validateCardAttachment($card_id, $this->accountProxy->id())) {
        $form_state->setErrorByName('card_id', $this->t('Please select  valid credit card for regular donation.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $donation_aim = $form_state->getValue('donation_aim');
    $politician = $form_state->getValue('politicians');
    $amount = $form_state->getValue('amount');
    $frequency = $form_state->getValue('frequency');
    $day = $form_state->getValue('date');
    $card_id = $form_state->getValue('card_id');
    $description = $donation_aim ? $donation_aim : $politician;
    if (empty($donation_aim) && empty($politician)) {
      $form_state->setErrorByName('donation_aim', $this->t('Please choose Donation aim OR Donation to politician'));
    }
    elseif (!empty($donation_aim) && !empty($politician)) {
      $this->messenger->addError($this->t('An illegal choice has been detected. Please contact the site administrator.'));
    }
    // Check if aim ID exists.
    elseif (!empty($donation_aim) && !array_key_exists($donation_aim, $this->options)) {
      $this->messenger->addError($this->t('An illegal choice has been detected. Please contact the site administrator.'));
    }
    // Check if politician ID exists.
    elseif (!empty($politician) && !array_key_exists($politician, $this->politicians)) {
      $this->messenger->addError($this->t('An illegal choice has been detected. Please contact the site administrator.'));
    }
    else {
      // TYPE 1 - AIM
      // TYPE 2 - Politician.
      $type = $donation_aim ? 1 : 2;
      $card_entity = $this->bankingUtils->getCardById($card_id);
      $this->donationUtils->addRegularDonationRecord([
        'trans_id'      => $card_entity->getTransactionId(),
        'card_id'       => $card_entity->getTbcId(),
        'user_id'       => $this->currentUser()->id(),
        'type'          => $type,
        'frequency'     => (int) $frequency,
        'payment_day'   => (int) $day,
        'amount'        => (int) $amount,
        'status'        => 'ACTIVE',
        'field_credit_card' => $card_id,
      ], $description);

      $this->messenger->addMessage($this->t('Regular donation was created'));
      $form_state->setRedirect('girchi_donations.regular_donations');
    }
  }

}
