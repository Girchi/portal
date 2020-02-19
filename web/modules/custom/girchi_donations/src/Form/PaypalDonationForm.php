<?php

namespace Drupal\girchi_donations\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\girchi_donations\Utils\DonationUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class SingleDonationForm.
 */
class PaypalDonationForm extends FormBase {

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
   * Current User.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

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
   * Current usd currency.
   *
   * @var mixed
   */
  protected $usd;
  /**
   * Current eur currency.
   *
   * @var mixed
   */
  protected $eur;
  /**
   * Currency options.
   *
   * @var mixed*/
  protected $currencies;

  /**
   * Keyvalue.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactory*/

  protected $keyValue;

  /**
   * Constructs a new UserController object.
   *
   * @param \Drupal\girchi_donations\Utils\DonationUtils $donationUtils
   *   Donation Utils.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   CurrentUser.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactory $keyValue
   *   keyValue.
   */
  public function __construct(DonationUtils $donationUtils,
                              MessengerInterface $messenger,
                              AccountProxy $currentUser,
                              KeyValueFactory $keyValue) {

    $this->donationUtils = $donationUtils;
    $this->messenger = $messenger;
    $this->currentUser = $currentUser;
    $this->politicians = $donationUtils->getPoliticians();
    $this->options = $donationUtils->getTerms();
    $this->usd = $keyValue->get('girchi_donations')->get('usd');
    $this->eur = $keyValue->get('girchi_donations')->get('eur');
    $this->currencies = ['usd' => 'USD', 'eur' => 'EUR', 'gel' => 'GEL'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('girchi_donations.donation_utils'),
      $container->get('messenger'),
      $container->get('current_user'),
      $container->get('keyvalue')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'paypal_donation_form';
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
        'placeholder' => $this->t('Amount'),
      ],
      '#required' => TRUE,
      '#weight' => '0',
    ];

    $form['currencies'] = [
      '#type' => 'select',
      '#options' => $this->currencies,
      '#required' => TRUE,
      '#options_attributes' => ['gel' => ['disabled' => TRUE], 'eur' => ['disabled' => TRUE]],
      '#default_value' => FALSE,
    ];

    $form['donation_aim'] = [
      '#type' => 'hidden',
      '#required' => FALSE,
      '#empty_value' => '',
      '#attributes' => [
        'class' => [
          'paypal-hidden-aim',
        ],
      ],
    ];

    $form['politicians'] = [
      '#type' => 'hidden',
      '#required' => FALSE,
      '#empty_value' => '',
      '#attributes' => [
        'class' => [
          'paypal-hidden-politician',
        ],
      ],
    ];

    $form['currency_usd'] = [
      '#title' => 'currency',
      '#type' => 'hidden',
      '#attributes' => [
        'id' => [
          'currency_girchi_usd',
        ],
      ],
      '#value' => $this->usd,
    ];

    $form['currency_eur'] = [
      '#title' => 'currency',
      '#type' => 'hidden',
      '#attributes' => [
        'id' => [
          'currency_girchi_eur',
        ],
      ],
      '#value' => $this->eur,
    ];
    $form['donation_id'] = [
      '#title' => 'donation_id',
      '#type' => 'hidden',
      '#attributes' => [
        'id' => [
          'donation_id',
        ],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#attributes' => [
        'class' => [
          'btn btn-lg btn-block btn-warning text-uppercase mt-4',
        ],
        'hidden' => TRUE,
      ],
      '#value' => $this->t('Donate'),
    ];

    $form['#cache'] = ['max-age' => 0];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $trans_id = $form_state->getValue('donation_id');
    $redirect = new RedirectResponse('/finish/paypal?trans_id=' . $trans_id);
    $redirect->send();
  }

}
