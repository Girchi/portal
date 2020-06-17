<?php

namespace Drupal\girchi_users\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Render\Renderer;
use Drupal\girchi_referral\GetUserReferralsService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UsersPageController.
 *
 * @package Drupal\girchi_users\Controller
 */
class UsersPageController extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * LoggerFactory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Referral service.
   *
   * @var \Drupal\girchi_referral\GetUserReferralsService
   */
  protected $referralService;

  /**
   * Drupal\Core\Render\Renderer definition.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * UsersPageController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   EntityTypeManager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   LoggerFactory.
   * @param \Drupal\girchi_referral\GetUserReferralsService $referralService
   *   ReferralService.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Drupal renderer.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager,
                              LoggerChannelFactoryInterface $loggerFactory,
  GetUserReferralsService $referralService,
  Renderer $renderer) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerFactory = $loggerFactory;
    $this->referralService = $referralService;
    $this->renderer = $renderer;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $container->get('girchi_referral.get_user_referrals'),
      $container->get('renderer')
    );
  }

  /**
   * Get Users.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Retruns html.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getReferrals(Request $request) {
    $userId = $request->request->get('userId');
    $referrals = $this->referralService->getReferralsWithInfo($userId);

    $build = [
      '#type' => 'markup',
      '#theme' => 'girchi_users_modal',
      '#referrals' => $referrals,
    ];
    $html = $this->renderer->renderRoot($build);
    $response = new Response();
    $response->setContent($html);

    return $response;
  }

}
