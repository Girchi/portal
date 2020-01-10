<?php

namespace Drupal\girchi_banking\Form;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\girchi_banking\Services\BankingUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Credit card delete form for user.
 */
class CreditCardDeleteUserForm extends FormBase {

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
   * Constructs a new SaveCreditCardForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   EM.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger.
   * @param \Drupal\girchi_banking\Services\BankingUtils $bankingUtils
   *   OM TBC payments.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory,
    BankingUtils $bankingUtils
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
    $this->bankingUtils = $bankingUtils;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $container->get('girchi_banking.utils')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'delete_credit_card_user_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['card_id'] = [
      '#title' => 'card_id',
      '#type' => 'hidden',
      '#attributes' => [
        'id' => [
          'delete-submit',
        ],
      ],
    ];

    $form['user_id'] = [
      '#title' => 'card_id',
      '#type' => 'hidden',
      '#value' => $this->currentUser()->id(),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
      '#attributes' => [
        'class' => [
          'btn btn-lg btn-danger text-uppercase mt-2',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $card_id = $form_state->getValue('card_id');

    if (!$card_id) {
      $form_state->setErrorByName('card_id', $this->t('Please select card'));
    }
    if ($this->currentUser()->id() !== $form_state->getValue('user_id')) {
      $form_state->setErrorByName('card_id', $this->t('Access denied'));
    }
    if (!$this->bankingUtils->validateCardAttachment($card_id, $this->currentUser()
      ->id())) {
      $form_state->setErrorByName('card_id', $this->t('Access denied'));
    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $card_id = $form_state->getValue('card_id');
      $card_storage = $this->entityTypeManager->getStorage('credit_card');
      $reg_d_storage = $this->entityTypeManager->getStorage('regular_donation');
      $reg_ds = $reg_d_storage->loadByProperties(['field_credit_card' => $card_id]);

      /** @var \Drupal\girchi_donations\Entity\RegularDonation $reg_d */
      foreach ($reg_ds as $reg_d) {
        $reg_d->delete();
      }
      /** @var \Drupal\girchi_banking\Entity\CreditCard $card_entity */
      $card_entity = $card_storage->load($card_id);
      $card_entity->delete();

      $this->messenger()->addMessage(sprintf('Card %s %s was deleted', $card_entity->getType(), $card_entity->getExpiry(TRUE)));
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->get('girchi_banking')->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->get('girchi_banking')->error($e->getMessage());
    }
    catch (EntityStorageException $e) {
      $this->loggerFactory->get('girchi_banking')->error($e->getMessage());
    }

  }

}
