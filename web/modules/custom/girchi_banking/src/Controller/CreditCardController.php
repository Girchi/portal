<?php

namespace Drupal\girchi_banking\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxy;
use Drupal\girchi_banking\Services\BankingUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 * Class CreditCardController.
 */
class CreditCardController extends ControllerBase {

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
   * Constructs a new CreditCardController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   EM.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger.
   * @param \Drupal\Core\Session\AccountProxy $accountProxy
   *   Account proxy.
   * @param \Drupal\girchi_banking\Services\BankingUtils $bankingUtils
   *   Banking Utils.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              LoggerChannelFactoryInterface $logger_factory,
                              AccountProxy $accountProxy,
                              BankingUtils $bankingUtils) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
    $this->accountProxy = $accountProxy;
    $this->bankingUtils = $bankingUtils;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $container->get('current_user'),
      $container->get('girchi_banking.utils')
    );
  }

  /**
   * Cards.
   */
  public function myCards() {
    try {
      $save_form = $this->formBuilder()->getForm('Drupal\girchi_banking\Form\SaveCreditCardForm');
      $delete_form = $this->formBuilder()->getForm('Drupal\girchi_banking\Form\CreditCardDeleteUserForm');
      $card_storage = $this->entityTypeManager->getStorage('credit_card');
      $cards = $card_storage->loadByProperties(['user_id' => $this->accountProxy->id()]);

      return [
        '#type' => 'markup',
        '#theme' => 'banking',
        '#save_form' => $save_form,
        '#delete_form' => $delete_form,
        '#cards' => $cards,
      ];

    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->get('girchi_banking')->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->get('girchi_banking')->error($e->getMessage());
    }

    return new JsonResponse("Unexpected server error", 500);
  }

}
