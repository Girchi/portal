<?php

namespace Drupal\girchi_paypal\Utils;

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Client for paypal api.
 */
class PayPalClient {

  /**
   * Client for paypal api.
   */
  public static function client() {
    return new PayPalHttpClient(self::environment());
  }

  /**
   * Setting PayPal SDK environment.
   */
  public static function environment() {
    $moduleHandler = \Drupal::service('module_handler');
    $modulePath = $moduleHandler->getModule('girchi_paypal')->getPath();

    // Load Paypal clinet id and secret pass.
    $dotEnv = new Dotenv();
    $dotEnv->load($modulePath . '/credentials/.credentials.env');
    $clientId = getenv("CLIENT_ID") ?: $_ENV['CLIENT_ID'];
    $clientSecret = getenv("CLIENT_SECRET") ?: $_ENV['CLIENT_SECRET'];
    if ($_ENV['ENV'] == 'production') {
      return new ProductionEnvironment($clientId, $clientSecret);
    }
    else {
      return new SandboxEnvironment($clientId, $clientSecret);
    }
  }

}
