<?php

namespace Drupal\girchi_leaderboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\girchi_donations\Utils\CreateGedTransaction;
use Drupal\girchi_donations\Utils\DonationUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LeaderboardForm.
 */
class LeaderboardForm extends FormBase {

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
   * Current currency.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface|mixed
   */
  protected $currency;

  /**
   * CreateGedTransaction.
   *
   * @var \Drupal\girchi_donations\Utils\CreateGedTransaction
   */
  protected $createGedTransaction;

  /**
   * Constructs a new UserController object.
   *
   * @param \Drupal\girchi_donations\Utils\DonationUtils $donationUtils
   *   Donation Utils.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   CurrentUser.
   * @param \Drupal\girchi_donations\Utils\CreateGedTransaction $createGedTransaction
   *   CreateGedTransaction.
   */
  public function __construct(DonationUtils $donationUtils, MessengerInterface $messenger, AccountProxy $currentUser, CreateGedTransaction $createGedTransaction) {
    $this->donationUtils = $donationUtils;
    $this->messenger = $messenger;
    $this->currentUser = $currentUser;
    $this->politicians = $donationUtils->getPoliticians();
    $this->options = $donationUtils->getTerms();
    $this->currency = $donationUtils->gedCalculator->getCurrency();
    $this->createGedTransaction = $createGedTransaction;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('girchi_donations.donation_utils'),
      $container->get('messenger'),
      $container->get('current_user'),
      $container->get('girchi_donations.create_ged_transaction')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'leaderboard_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['user'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      "#bundle" => "user",
      '#attributes' => [
        'class' => [
          'form-control form-control-lg',
        ],
        'placeholder' => $this->t('Enter partner name'),
      ],
    ];
    $form['amount'] = [
      '#type' => 'number',
      '#attributes' => [
        'class' => [
          'form-control form-control-lg',
        ],
        'placeholder' => $this->t('Enter amount of GEL'),
      ],
      '#required' => TRUE,
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
    $form['submit'] = [
      '#type' => 'submit',
      '#attributes' => [
        'class' => [
          'btn btn-lg btn-block btn-warning text-uppercase mt-4',
        ],
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
    $donation_aim = $form_state->getValue('donation_aim');
    $politician = $form_state->getValue('politicians');
    $amount = $form_state->getValue('amount');
    $user = $form_state->getValue('user');
    $description = $donation_aim ? $donation_aim : $politician;
    if (empty($donation_aim) && empty($politician)) {
      $this->messenger->addError($this->t('Please choose Donation aim OR Donation to politician'));
      $form_state->setRebuild();
    }
    elseif (!empty($donation_aim) && !empty($politician)) {
      $this->messenger->addError($this->t('Please choose Donation aim OR Donation to politician'));
      $form_state->setRebuild();
    }
    else {
      $ged_trans_id = $this->createGedTransaction->createGedTransaction($user, $amount);
      if ($ged_trans_id) {
        // TYPE 1 - AIM
        // TYPE 2 - Politician.
        $type = $donation_aim ? 1 : 2;
        $valid = $this->donationUtils->addDonationRecord(
          $type,
          [
            'trans_id' => '1111111111111111111111111111',
            'amount' => (int) $amount,
            'user_id' => $user,
            'field_source' => 'manual',
            'status' => 'OK',
          ],
          $description);
        if (!$valid) {
          $this->messenger->addError($this->t('Error'));
          $form_state->setRebuild();
        }
        else {
          $this->messenger->addMessage($this->t('Donation was successfully created!'));
        }
      }
      else {
        $this->messenger->addError($this->t('Error'));
        $form_state->setRebuild();
      }
    }
  }

}
