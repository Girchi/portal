<?php

namespace Drupal\girchi_paypal\Utils;

use Drupal\Core\Config\ConfigFactory;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;

/**
 * Client for paypal api.
 */
class PayPalClient {

  /**
   * Config factory.
   *
   * @var configFactory*/
  protected $configFactory;

  /**
   * PayPalClient constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   ConfigFactory.
   */
  public function __construct(ConfigFactory $configFactory) {
    $this->configFactory = $configFactory->get("girchi_paypal.paypalsettings");
  }

  /**
   * Client for paypal api.
   */
  public function client() {
    return new PayPalHttpClient($this->environment());
  }

  /**
   * Setting PayPal SDK environment.
   */
  public function environment() {
    // Load Paypal clinet id and secret pass.
    $clientId = $this->configFactory->get('client_id');
    $clientSecret = $this->configFactory->get('client_secret');
    $paypalEnv = $this->configFactory->get('environment');
    if ($paypalEnv == 'production') {
      return new ProductionEnvironment($clientId, $clientSecret);
    }
    else {
      return new SandboxEnvironment($clientId, $clientSecret);
    }
  }

}
