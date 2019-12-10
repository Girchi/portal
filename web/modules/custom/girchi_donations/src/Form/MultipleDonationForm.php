<?php

namespace Drupal\girchi_donations\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
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
   * Constructs a new UserController object.
   *
   * @param \Drupal\girchi_donations\Utils\DonationUtils $donationUtils
   *   Donation Utils.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger.
   * @param \Drupal\om_tbc_payments\Services\PaymentService $omediaPayment
   *   Omedia Payment.
   */
  public function __construct(DonationUtils $donationUtils, MessengerInterface $messenger, PaymentService $omediaPayment) {
    $this->donationUtils = $donationUtils;
    $this->messenger = $messenger;
    $this->omediaPayment = $omediaPayment;
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
        $container->get('om_tbc_payments.payment_service')
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
      '#type' => 'select',
      '#options' => $this->options,
      '#required' => FALSE,
      '#empty_value' => '',
      '#empty_option' => $this->t('- Select aim -'),
    ];
    $form['politicians'] = [
      '#type' => 'select',
      '#options' => $this->politicians,
      '#required' => FALSE,
      '#empty_value' => '',
      '#empty_option' => $this->t('- Select politician -'),
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
    $form['submit'] = [
      '#type' => 'submit',
      '#attributes' => [
        'class' => [
          'btn btn-lg btn-block btn-warning text-uppercase mt-4',
        ],
      ],
      '#value' => $this->t('Donate'),
    ];

    return $form;
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

    $description = $donation_aim ? $donation_aim : $politician;
    if (empty($donation_aim) && empty($politician)) {
      $this->messenger->addError('Please choose Donation aim OR Donation to politician');
      $form_state->setRebuild();
    }
    else {
      // TYPE 1 - AIM
      // TYPE 2 - Politician.
      $type = $donation_aim ? 1 : 2;
      $response = $this->omediaPayment->saveCard($amount, $description);
      if ($response !== NULL) {
        $this->getLogger('girchi_donations')->info('Card was saved.');
        $transaction_id = $response['transaction_id'];
        $card_id = $response['card_id'];
        $this->donationUtils->addRegularDonationRecord([
          'trans_id'      => $transaction_id,
          'card_id'       => $card_id,
          'user_id'       => $this->currentUser()->id(),
          'type'          => $type,
          'frequency'     => (int) $frequency,
          'payment_day'   => (int) $day,
          'amount'        => (int) $amount,
          'status'        => 'INITIAL',
        ], $description);
        $this->omediaPayment->makePayment($transaction_id);
      }
      else {
        $this->getLogger('girchi_donations')->error('Error saving  while saving card.');
      }
    }
  }

}
