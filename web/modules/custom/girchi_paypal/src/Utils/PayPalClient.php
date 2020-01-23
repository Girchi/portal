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
    $paypal_config = \Drupal::config('girchi_paypal.paypalsettings');
    $dotEnv = new Dotenv();
    $dotEnv->load($modulePath . '/credentials/.credentials.env');
    $clientId = $paypal_config->get('client_id');
    $clientSecret = $paypal_config->get('client_secret');
    $paypalEnv = $paypal_config->get('environment');
    if ($paypalEnv == 'production') {
      return new ProductionEnvironment($clientId, $clientSecret);
    }
    else {
      return new SandboxEnvironment($clientId, $clientSecret);
    }
  }

}
