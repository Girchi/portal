<?php

namespace Drupal\girchi_donations\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\girchi_banking\Services\BankingUtils;
use Drupal\girchi_donations\Utils\DonationUtils;
use Drupal\om_tbc_payments\Services\PaymentService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Regular donation edit forms.
 *
 * @ingroup girchi_donations
 */
class RegularDonationForm extends ContentEntityForm {

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
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(DonationUtils $donationUtils,
                              MessengerInterface $messenger,
                              PaymentService $omediaPayment,
                              AccountProxy $accountProxy,
                              BankingUtils $bankingUtils,
                              EntityRepositoryInterface $entity_repository,
                              EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL,
                              TimeInterface $time = NULL) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->donationUtils = $donationUtils;
    $this->bankingUtils = $bankingUtils;
    $this->messenger = $messenger;
    $this->omediaPayment = $omediaPayment;
    $this->accountProxy = $accountProxy;
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
      $container->get('girchi_banking.utils'),
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\girchi_donations\Entity\RegularDonation */
    $form = parent::buildForm($form, $form_state);

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
      '#default_value' => $this->entity->get('amount')->value,
    ];
    $form['frequency'] = [
      '#type' => 'select',
      '#attributes' => [
        'class' => [
          'selected',
        ],
      ],
      '#options' => [
        '1' => $this->t('Every month'),
        '3' => $this->t('Once in every 3 months'),
        '6' => $this->t('Once in every 6 months'),
      ],
      '#required' => TRUE,
      '#default_value' => $this->entity->get('frequency')->value,
    ];
    $form['date'] = [
      '#default_value' => $this->entity->get('payment_day')->value,
      '#type' => 'select',
      '#attributes' => [
        'class' => [
          'selected',
        ],
      ],
      '#options' => $this->donationUtils->getMonthDates(),
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

    if ($this->entity->get('type')->value == 1) {
      $form['donation_aim'] = [
        '#type' => 'hidden',
        '#required' => FALSE,
        '#empty_value' => '',
        '#attributes' => [
          'class' => [
            'tbc-multiple-hidden-aim',
            'selected',
          ],
        ],
        '#default_value' => $this->entity->get('aim_id')->entity->id(),
      ];
    }
    else {
      $form['politicians'] = [
        '#type' => 'hidden',
        '#required' => FALSE,
        '#empty_value' => '',
        '#attributes' => [
          'class' => [
            'tbc-multiple-hidden-politician',
            'selected',
          ],
        ],
        '#default_value' => $this->entity->get('politician_id')->entity->id(),
      ];
    }

    $form['actions']['submit']['#attributes'] = [
      'class' => [
        'btn btn-lg btn-block btn-success text-uppercase mt-4',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    try {
      $amount = $form_state->getValue('amount');
      $frequency = $form_state->getValue('frequency');
      $day = $form_state->getValue('date');
      $donation_aim = $form_state->getValue('donation_aim');
      $politician = $form_state->getValue('politicians');
      $this->entity->set('amount', $amount);
      $this->entity->set('frequency', $frequency);
      $this->entity->set('payment_day', $day);
      if ($this->entity->get('type')->value == '1') {
        $this->entity->set('aim_id', $donation_aim);
      }
      else {
        $this->entity->set('politician_id', $politician);
      }
      $this->entity->save();
      $this->messenger()->addMessage($this->t('Regular donation was successfully updated'));
      $form_state->setRedirect('girchi_donations.regular_donations');
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error'));
      $this->getLogger('girchi_donations')->error($e->getMessage());
    }

  }

}
