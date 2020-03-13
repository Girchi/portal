<?php

namespace Drupal\girchi_users\EventSubscriber;

use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Session\AccountProxy;
use Drupal\girchi_users\Event\UserLoginEvent;
use Drupal\girchi_users\GenerateJwtService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Authentication Subsciriber.
 */
class AuthenticationSubscriber implements EventSubscriberInterface {
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
   * Generate jwt.
   *
   * @var \Drupal\girchi_users\GenerateJwtService
   */
  protected $generateJWT;

  /**
   * Constructs a new AuthenticationSubscriber object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   Logger messages.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request.
   * @param \Drupal\Core\Session\AccountProxy $accountProxy
   *   Account proxy.
   * @param \Drupal\girchi_users\GenerateJwtService $generateJWT
   *   Generate jwt token.
   */
  public function __construct(LoggerChannelFactory $loggerFactory, RequestStack $requestStack, AccountProxy $accountProxy, GenerateJwtService $generateJWT) {
    $this->generateJWT = $generateJWT;
    $this->loggerFactory = $loggerFactory->get('girchi_users');
    $this->request = $requestStack;
    $this->accountProxy = $accountProxy;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      UserLoginEvent::USER_LOGIN => 'onUserLogin',
      KernelEvents::RESPONSE => 'onResponse',
    ];

  }

  /**
   * On user login.
   */
  public function onUserLogin(UserLoginEvent $event) {
    try {
      $this->generateJWT->generateJwt();
    }
    catch (\Exception $e) {
      $this->loggerFactory->error($e);
    }
  }

  /**
   * On KernelEvent response.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   Event.
   */
  public function onResponse(FilterResponseEvent $event) {
    if (!empty($this->request->getCurrentRequest()->getSession()->get('g-u-at'))) {
      $jwt = $this->request->getCurrentRequest()->getSession()->get('g-u-at');
      $refresh_token = $this->request->getCurrentRequest()->getSession()->get('g-u-rt');
      $jwt_cookie = new Cookie('g-u-at', $jwt, time() + 18000);
      $refresh_token_cookie = new Cookie('g-u-rt', $refresh_token, time() + 18000);
      $event->getResponse()->headers->setCookie($jwt_cookie);
      $event->getResponse()->headers->setCookie($refresh_token_cookie);
    }

    if ($this->accountProxy->isAnonymous() && !empty($this->request->getCurrentRequest()->getSession()->get('g-u-at'))) {
      $event->getResponse()->headers->clearCookie('g-u-at');
      $event->getResponse()->headers->clearCookie('g-u-rt');
    }

  }

}
