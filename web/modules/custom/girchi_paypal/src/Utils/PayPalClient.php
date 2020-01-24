<?php

namespace Drupal\girchi_paypal\Utils;

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;

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
    // Load Paypal clinet id and secret pass.
    $paypal_config = \Drupal::config('girchi_paypal.paypalsettings');
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
