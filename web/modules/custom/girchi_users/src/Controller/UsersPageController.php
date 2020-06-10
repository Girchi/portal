<?php

namespace Drupal\girchi_users\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\girchi_referral\GetUserReferralsService;
use Drupal\image\Entity\ImageStyle;
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
   * UsersPageController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   EntityTypeManager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   LoggerFactory.
   * @param \Drupal\girchi_referral\GetUserReferralsService $referralService
   *   ReferralService.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager,
                              LoggerChannelFactoryInterface $loggerFactory,
  GetUserReferralsService $referralService) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerFactory = $loggerFactory;
    $this->referralService = $referralService;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $container->get('girchi_referral.get_user_referrals')
    );
  }

  /**
   * Get Users.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   *   Request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *
   *   Response
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getPoliticianSupporters(Request $request) {
    $userId = $request->request->get('userId');
    $referrals = $this->referralService->getUserReferrals($userId);
    $user_info = [];
    foreach ($referrals['referralUsers'] as $user) {
      if (!empty($user->get('user_picture')[0])) {
        $img_id = $user->get('user_picture')[0]->getValue()['target_id'];
        $img_file = $this->entityTypeManager->getStorage('file')->load($img_id);
        $style = ImageStyle::load('party_member');
        $img_url = $style->buildUrl($img_file->getFileUri());
      }
      $first_name = $user->get('field_first_name')->value;
      $last_name = $user->get('field_last_name')->value;
      $user_info[] = [
        'img_url' => $img_url,
        'name' => implode(" ", [$first_name, $last_name]),
        'id' => $user->id(),
      ];

    }
    dump($user_info);
    $response = new Response();
    return $response->setContent("Hello");

  }

}
