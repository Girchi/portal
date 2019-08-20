<?php

namespace Drupal\om_tbc_payments\Controller;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\KeyValueStore\KeyValueFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class PaymentController.
 */
class PaymentController extends ControllerBase {

  /**
   * ConfigFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * KeyValue.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactory
   */
  protected $keyValue;

  /**
   * Construct.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   ConfigFactory.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactory $keyValue
   *   Key value.
   */
  public function __construct(ConfigFactory $configFactory, KeyValueFactory $keyValue) {
    $this->configFactory = $configFactory;
    $this->keyValue = $keyValue->get('om_tbc_payments');

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('keyvalue')
    );
  }

  /**
   * Prepare.
   *
   *  *
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   *   Request.
   *
   * @return mixed
   *   Return Hello string.
   */
  public function prepare(Request $request) {
    $key = $request->query->get('key');
    if (!$key) {
      $this->getLogger('om_tbc_payments')->error('Trans ID Hash key is missing on redirect.');
      return new JsonResponse('Key is missing', Response::HTTP_BAD_REQUEST);
    }
    $id = $this->keyValue->get($key);
    if (!$id && !$request->headers->get('referer')) {
      $this->getLogger('om_tbc_payments')->error('Invalid transaction ID.');
      return new JsonResponse('ID is missing', Response::HTTP_BAD_REQUEST);
    }
    elseif (strlen($id) !== 28) {
      $this->getlogger('om_tbc_payments')->error('Invalid transaction ID.');
      return new JsonResponse('Invalid ID', Response::HTTP_NOT_FOUND);
    }
    else {
      $this->keyValue->delete($key);
      $this->getLogger('om_tbc_payments')->info('User was redirected to Merchant.');
      return [
        '#type' => 'markup',
        '#theme' => 'prepare',
        '#id' => $id,
      ];
    }

  }

}
