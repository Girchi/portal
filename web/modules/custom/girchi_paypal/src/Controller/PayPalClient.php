<?php

namespace Drupal\girchi_paypal\Controller;

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;

// Or error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

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
    $clientId = getenv("CLIENT_ID") ?: "AUCemHy3DQN_XhP9dqNahbnOwM2kawkNr-shr7KIED1F3cGDObPi2Iw2UhTUPMSKmnT6e_1i7C9HhyJ4";
    $clientSecret = getenv("CLIENT_SECRET") ?: "EIZhdPEehTXNMpNBT7r2ZTeF-LhWBSapuJ8Zg4ynN3xkRa8M15_h2GLWX6iVuXKnzLDIJwb7V6hYoV5e";
    return new SandboxEnvironment($clientId, $clientSecret);
  }

}
