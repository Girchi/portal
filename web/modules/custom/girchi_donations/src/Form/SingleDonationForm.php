<?php

namespace Drupal\girchi_donations\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\girchi_donations\Utils\DonationUtils;
use Drupal\om_tbc_payments\Services\PaymentService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SingleDonationForm.
 */
class SingleDonationForm extends FormBase {

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
   * Current User.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Constructs a new UserController object.
   *
   * @param \Drupal\girchi_donations\Utils\DonationUtils $donationUtils
   *   Donation Utils.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger.
   * @param \Drupal\om_tbc_payments\Services\PaymentService $omediaPayment
   *   Payments.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   CurrentUser.
   */
  public function __construct(DonationUtils $donationUtils, MessengerInterface $messenger, PaymentService $omediaPayment, AccountProxy $currentUser) {
    $this->donationUtils = $donationUtils;
    $this->messenger = $messenger;
    $this->omediaPayment = $omediaPayment;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('girchi_donations.donation_utils'),
      $container->get('messenger'),
      $container->get('om_tbc_payments.payment_service'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'single_donation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // dump($this->donationUtils->gedCalculator->getCurrency());die;
    $politicians = $this->donationUtils->getPoliticians();
    $options = $this->donationUtils->getTerms();

    $form['amount'] = [
      '#type' => 'number',
      '#attributes' => [
        'class' => [
          'form-control form-control-lg',
        ],
        'placeholder' => $this->t('Enter amount of GEL'),
      ],
      '#required' => TRUE,
      '#weight' => '0',
    ];
    $form['donation_aim'] = [
      '#type' => 'select',
      '#options' => $options,
      '#required' => FALSE,
      '#empty_value' => '',

    ];
    $form['politicians'] = [
      '#type' => 'select',
      '#options' => $politicians,
      '#required' => FALSE,
      '#empty_value' => '',
    ];
    $form['currency'] = [
      '#title' => 'currency',
      '#type' => 'hidden',
      '#attributes' => [
        'id' => [
          'currency_girchi',
        ],
      ],
      '#value' => $this->donationUtils->gedCalculator->getCurrency(),
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
    $description = $donation_aim ? $donation_aim : $politician;

    if (empty($donation_aim) && empty($politician)) {
      $this->messenger->addError($this->t('Please choose Donation aim OR Donation to politician'));
      $form_state->setRebuild();
    }
    else {
      // TYPE 1 - AIM
      // TYPE 2 - Politician.
      $type = $donation_aim ? 1 : 2;
      $transaction_id = $this->omediaPayment->generateTransactionId($amount, "test");
      $this->donationUtils->addDonationRecord(
        $type,
        [
          'trans_id' => $transaction_id,
          'amount' => (int) $amount,
          'user_id' => $this->currentUser->id(),
        ],
        $description);

      return $this->omediaPayment->makePayment($transaction_id);
    }
  }

}
