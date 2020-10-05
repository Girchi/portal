<?php

namespace Drupal\girchi_sms;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\Client;
use Symfony\Component\Dotenv\Dotenv;

/**
 * SmsSender service.
 */
class SmsSender {

  /**
   * Url for api.
   *
   * @var string
   */
  protected $apiUrl;

  /**
   * Key for api.
   *
   * @var string
   */
  protected $apiKey;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The girchi_sms.utils service.
   *
   * @var \Drupal\girchi_sms\Utils
   */
  protected $girchiSmsUtils;

  /**
   * HttpClient.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Constructs a SmsSender object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\girchi_sms\Utils $girchi_sms_utils
   *   The girchi_sms.utils service.
   * @param \GuzzleHttp\Client $httpClient
   *   HttpClient.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   Module handler.
   */
  public function __construct(LoggerChannelFactoryInterface $logger,
                              EntityTypeManagerInterface $entity_type_manager,
                              Utils $girchi_sms_utils,
                              Client $httpClient,
                              ModuleHandler $moduleHandler) {

    $this->logger = $logger;
    $this->entityTypeManager = $entity_type_manager;
    $this->girchiSmsUtils = $girchi_sms_utils;
    $this->httpClient = $httpClient;
    $this->apiUrl = "http://smsoffice.ge/api/v2/send/";
    $modulePath = $moduleHandler->getModule('girchi_sms')->getPath();

    // Load api key.
    $this->dotEnv = new Dotenv();
    $this->dotEnv->load($modulePath . '/key/.key.env');

  }

  /**
   * Method description.
   */
  public function sendMultipleSms($options) {
    $numbers = $this->girchiSmsUtils->getNumbersByFilters($options);
    $options = [
      'form_params' => [
        'key' => $_ENV['API_KEY'],
        'destination' => $numbers,
        'sender' => 'Girchi',
        'content' => $options['message'],
      ],
    ];
    $res = $this->httpClient->post($this->apiUrl, $options);
    return $res->getBody()->getContents();
  }

}
