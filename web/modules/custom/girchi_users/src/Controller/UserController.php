<?php

namespace Drupal\girchi_users\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\girchi_notifications\NotifyAdminService;
use Drupal\girchi_users\GenerateJwtService;
use Drupal\girchi_utils\TaxonomyTermTree;
use Drupal\social_auth\SocialAuthDataHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class UserController.
 */
class UserController extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;


  /**
   * Drupal\social_auth\SocialAuthDataHandler.
   *
   * @var \Drupal\social_auth\SocialAuthDataHandler
   */
  protected $SocialAuthDataHandler;


  /**
   * LoggerFactory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * ConfigFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * User.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * Generate jwt.
   *
   * @var \Drupal\girchi_users\GenerateJwtService
   */
  protected $generateJWT;

  /**
   * NotifyAdminService.
   *
   * @var \Drupal\girchi_notifications\NotifyAdminService
   */
  protected $notifyAdmin;

  /**
   * Json.
   *
   * @var \Drupal\Component\Serialization\Json
   */
  public $json;

  /**
   * TaxonomyTermTree.
   *
   * @var \Drupal\girchi_utils\TaxonomyTermTree
   */
  public $taxonomyTermTree;

  /**
   * Constructs a new UserController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity Type Manager.
   * @param \Drupal\social_auth\SocialAuthDataHandler $socialAuthDataHandler
   *   Social Auth Data Handler.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   LoggerFactory.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   ConfigFactory.
   * @param \Drupal\girchi_users\GenerateJwtService $generateJWT
   *   GenerateJwtService.
   * @param \Drupal\girchi_notifications\NotifyAdminService $notifyAdmin
   *   NotifyAdminService.
   * @param \Drupal\Component\Serialization\Json $json
   *   Json.
   * @param \Drupal\girchi_utils\TaxonomyTermTree $taxonomyTermTree
   *   Taxonomy term tree.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              SocialAuthDataHandler $socialAuthDataHandler,
                              LoggerChannelFactoryInterface $loggerFactory,
                              ConfigFactory $configFactory,
                              GenerateJwtService $generateJWT,
                              NotifyAdminService $notifyAdmin,
                              Json $json,
                              TaxonomyTermTree $taxonomyTermTree) {
    $this->entityTypeManager = $entity_type_manager;
    $this->SocialAuthDataHandler = $socialAuthDataHandler;
    $this->loggerFactory = $loggerFactory;
    $this->configFactory = $configFactory;
    $this->generateJWT = $generateJWT;
    $this->notifyAdmin = $notifyAdmin;
    $this->json = $json;
    $this->taxonomyTermTree = $taxonomyTermTree;
    try {
      $userStorage = $this->entityTypeManager->getStorage('user');
      $current_user_id = $this->currentUser()->id();
      $this->user = $userStorage->load($current_user_id);

    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->get('girchi_users')->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->get('girchi_users')->error($e->getMessage());
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('social_auth.data_handler'),
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('girchi_users.generate_jwt'),
      $container->get('girchi_notifications.notify_admin'),
      $container->get('serialization.json'),
      $container->get('girchi_utils.taxonomy_term_tree')
    );
  }

  /**
   * Social auth password.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   *   Request.
   *
   * @return array|RedirectResponse
   *
   *   Return template
   */
  public function socialAuthPassword(Request $request) {

    $token = $this->SocialAuthDataHandler->get('social_auth_facebook_access_token');
    $regions = $this->taxonomyTermTree->load('regions');
    $users = $this->getUsers();

    if ($this->user->get('field_social_auth_password')->getValue()) {
      $password_check = $this->user->get('field_social_auth_password')->getValue()[0]['value'];
    }
    else {
      $password_check = FALSE;
    }

    $config = $this->configFactory->get('om_site_settings.site_settings');
    $subtitle = $config->get('createpass');

    if ($token && !$password_check) {
      return [
        '#type' => 'markup',
        '#theme' => 'girchi_users',
        '#uid' => $this->user->id(),
        '#subtitle' => $subtitle,
        '#regions' => $regions,
        '#users' => $users,
      ];
    }
    else {
      $response = new RedirectResponse("/user");
      $response->send();
      return $response;
    }

  }

  /**
   * Password Check.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   *   Request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *
   *   JsonResponse
   */
  public function passwordConfirm(Request $request) {

    try {
      $phoneNumber = $request->request->get('phoneNumber');
      $country = $request->request->get('country');
      $name = $request->request->get('name');
      $lastName = $request->request->get('lastName');
      $idNumber = $request->request->get('idNumber');
      $fbUrl = $request->request->get('fbUrl');
      $pass = $request->request->get('pass');
      $uid = $request->request->get('uid');
      $referral = $request->request->get('referral');
      if (empty($pass)) {
        return new JsonResponse('Password is empty');
      }

      /** @var \Drupal\user\Entity\User $user */
      if ($this->user) {
        if ($this->user->id() === $uid) {
          $this->user->setPassword($pass);
          $this->user->set('field_region', ['target_id' => $country]);
          $this->user->set('field_referral', ['target_id' => $referral]);
          $this->user->set('field_tel', $phoneNumber);
          $this->user->set('field_first_name', $name);
          $this->user->set('field_last_name', $lastName);
          $this->user->set('field_personal_id', $idNumber);
          $this->user->set('field_facebook_url', $fbUrl);
          $this->user->set('field_social_auth_password', TRUE);
          $this->user->save();
          return new JsonResponse('success');
        }
        else {
          return new JsonResponse('Unauthorized User');
        }
      }
      else {
        return new JsonResponse('User Not Found');
      }
    }
    catch (EntityStorageException $e) {
      $this->loggerFactory->get('girchi_users')->error($e->getMessage());
    }

    return new JsonResponse('Failed');

  }

  /**
   * Add Favorite News.
   *
   * @param int $nid
   *   Node id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function addFavoriteNews($nid) {
    /** @var \Drupal\node\Entity\NodeStorage $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node = $node_storage->load($nid);
    $fav_news_array = $this->user->{'field_favorite_news'}->getValue();
    $array_column = array_column($fav_news_array, 'target_id');
    $key = array_search($nid, $array_column);
    if ($key == NULL) {
      $this->user->{'field_favorite_news'}[] = $node;
      $this->user->save();
    }

    return new JsonResponse("success");
  }

  /**
   * Remove Favorite News.
   *
   * @param int $nid
   *   Node id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeFavoriteNews($nid) {
    $news = $this->user->get('field_favorite_news')->getValue();
    $array_column = array_column($news, 'target_id');
    if ($array_column) {
      $key = array_search($nid, $array_column);
      if ($key !== NULL) {
        $this->user->get('field_favorite_news')->removeItem($key);
        $this->user->save();
      }
    }

    return new JsonResponse("success");
  }

  /**
   * Generate jwt refresh token.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json.
   */
  public function jwtRefreshToken(Request $request) {
    $current_refresh_token = $request->cookies->get('g-u-rt');
    $user_refresh_token = $this->user->get('field_refresh_token')->value;

    if ($current_refresh_token == $user_refresh_token) {
      $tokens = $this->generateJWT->generateJwt();
      return new JsonResponse(["status" => "success", "tokens" => $tokens]);
    }
    return new JsonResponse(["status" => "fail"]);

  }

  /**
   * RequestBadges.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json.
   */
  public function requestBadges(Request $request) {
    try {
      $badge_id = $request->request->get('badgeId');
      $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($badge_id);
      $term_status = $term->get('field_publicity')->value;
      $appearance_array = [
        'visibility' => FALSE,
        'selected' => TRUE,
        'approved' => FALSE,
        'status_message' => $this->t('The request is being processed'),
        'earned_badge' => FALSE,
      ];
      $encoded_Value = $this->json->encode($appearance_array);
      // If term status is equal to false
      // Send notification to admin
      // for approving that badge.
      if ($term_status == FALSE) {
        $this->notifyAdmin->badgeRequest($this->user->id(), $badge_id);
        $this->user->get('field_badges')->appendItem([
          'target_id' => $badge_id,
          'value' => $encoded_Value,
        ]);
        $this->user->save();
        return new JsonResponse("success");
      }

    }
    catch (EntityStorageException $e) {
      $this->loggerFactory->get('girchi_users')->error($e->getMessage());
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->get('girchi_users')->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->get('girchi_users')->error($e->getMessage());
    }
    return new JsonResponse("fail");

  }

  /**
   * Get all users as potential referrals.
   *
   * @return array
   *   Options.
   */
  public function getUsers() {
    $options = [];
    try {
      /** @var \Drupal\user\UserStorage $user_storage */
      $user_storage = $this->entityTypeManager->getStorage('user');

      // Get politicians who's rating in party list is not equal to 0.
      $user_ids = $user_storage->getQuery()
        ->condition('field_first_name', NULL, 'IS NOT NULL')
        ->condition('field_last_name', NULL, 'IS NOT NULL')
        ->condition('field_referral', NULL, 'IS NOT NULL')
        ->execute();

      $users = $user_storage->loadMultiple($user_ids);

      if ($users) {
        /** @var \Drupal\user\Entity\User $user */
        foreach ($users as $user) {
          $first_name = $user->get('field_first_name')->value;
          $last_name = $user->get('field_last_name')->value;
          if ($user->get('user_picture')->entity) {
            $profilePictureEntity = $user->get('user_picture')->entity;
            $profilePicture = $profilePictureEntity->getFileUri();
          }
          else {
            $profilePicture = NULL;
          }
          $options[$user->id()] = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'img' => $profilePicture,
            'id' => $user->id(),
          ];

        }
      }

      return $options;
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->get('girchi_users')->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->get('girchi_users')->error($e->getMessage());
    }

    return $options;
  }

}
