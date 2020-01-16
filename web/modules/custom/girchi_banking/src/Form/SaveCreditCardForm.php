<?php

namespace Drupal\girchi_banking\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\girchi_banking\Services\BankingUtils;
use Drupal\om_tbc_payments\Services\PaymentService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Class SaveCreditCardForm.
 */
class SaveCreditCardForm extends FormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Drupal\girchi_banking\BankingUtils definition.
   *
   * @var \Drupal\girchi_banking\Services\BankingUtils
   */
  protected $bankingUtils;

  /**
   * Payment service.
   *
   * @var \Drupal\om_tbc_payments\Services\PaymentService
   */
  protected $omediaPayment;

  /**
   * Constructs a new SaveCreditCardForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   EM.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger.
   * @param \Drupal\girchi_banking\Services\BankingUtils $bankingUtils
   *   Banking utils.
   * @param \Drupal\om_tbc_payments\Services\PaymentService $omediaPayment
   *   OM TBC payments.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory,
    BankingUtils $bankingUtils,
    PaymentService $omediaPayment
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
    $this->bankingUtils = $bankingUtils;
    $this->omediaPayment = $omediaPayment;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $container->get('girchi_banking.utils'),
      $container->get('om_tbc_payments.payment_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'save_credit_card_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#attributes' => [
        'class' => [
          'btn btn-lg btn-block text-uppercase mt-2',
        ],
        'id' => [
          'save-submit',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $tbc_response = $this->omediaPayment->saveCard(1, 'Girchi.com');
    if ($tbc_response) {
      $trans_id = $tbc_response['transaction_id'];
      $card_id = $tbc_response['card_id'];
      if ($this->bankingUtils->prepareCard($trans_id, $card_id)) {
        $this->omediaPayment->makePayment($trans_id);
      }
      else {
        $this->messenger->addError($this->t('Error while saving card'));
        $form_state->setRebuild();
      }
    }
    else {
      $this->messenger->addError($this->t('Error while saving card'));
      $form_state->setRebuild();
    }
  }

}
