<?php

namespace Drupal\girchi_users;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Session\AccountProxy;
use Firebase\JWT\JWT;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class GenerateJwtService.
 */
class GenerateJwtService {
  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * Account proxy.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $accountProxy;

  /**
   * EntityTypeManagerInterface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new AuthenticationSubscriber object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   Logger messages.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request.
   * @param \Drupal\Core\Session\AccountProxy $accountProxy
   *   Account proxy.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager.
   */
  public function __construct(LoggerChannelFactory $loggerFactory, RequestStack $requestStack, AccountProxy $accountProxy, EntityTypeManagerInterface $entityTypeManager) {
    $this->loggerFactory = $loggerFactory->get('girchi_users');
    $this->request = $requestStack;
    $this->accountProxy = $accountProxy;
    $this->entityTypeManager = $entityTypeManager;

  }

  /**
   * Generate jwt token.
   */
  public function generateJwt() {
    try {
      $dotEnv = new Dotenv();
      $dotEnv->load('modules/custom/girchi_users' . '/Credentials/.cred.env');
      $privateKey = $_ENV['PRIVATE_KEY'];

      // Id of logged in user.
      $uid = $this->accountProxy->id();
      $current_user = $this->entityTypeManager->getStorage('user')->load($uid);
      // Generate random hash for refresh token.
      $refresh_token = bin2hex(random_bytes(20)) . uniqid();
      // Set refresh token to user field field_refresh_token.
      $current_user->set('field_refresh_token', $refresh_token);
      $current_user->save();

      // Create payload.
      $payload = [
        'user_id' => $uid,
        'exp' => strtotime('+30 minutes'),
      ];

      // Generate JWT token.
      $jwt = JWT::encode($payload, $privateKey, 'RS256');
      // Set jwt in session.
      $session = $this->request->getCurrentRequest()->getSession();
      // Girchi user access token.
      $session->set('g-u-at', $jwt);
      // Girchi user refresh token.
      $session->set('g-u-rt', $refresh_token);
      return ["accessToken" => $jwt, "refreshToken" => $refresh_token];
    }
    catch (\Exception $e) {
      $this->loggerFactory->error($e);

    }
  }

}
