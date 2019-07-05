<?php

namespace Drupal\girchi_users\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\social_auth\SocialAuthDataHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Zend\Diactoros\Response\JsonResponse;

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
   * User.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * Constructs a new UserController object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, SocialAuthDataHandler $socialAuthDataHandler) {

    $this->entityTypeManager = $entity_type_manager;
    $this->SocialAuthDataHandler = $socialAuthDataHandler;
    try {
      $userStorage = $this->entityTypeManager->getStorage('user');
      $current_user_id = $this->currentUser()->id();
      $this->user = $userStorage->load($current_user_id);

    }
    catch (InvalidPluginDefinitionException $e) {

    }
    catch (PluginNotFoundException $e) {

    }

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('social_auth.data_handler')
    );
  }

  /**
   * Social auth password.
   *
   *   Return Hello string.
   */
  public function socialAuthPassword() {

    $token = $this->SocialAuthDataHandler->get('social_auth_facebook_access_token');
    $password_check = $this->user->get('field_social_auth_password')->getValue()[0]['value'];
    if ($token && !$password_check) {
      return [
        '#type' => 'markup',
        '#theme' => 'girchi_users',
      ];
    }
    else {
      $response = new RedirectResponse("/user");
      $response->send();
      return $response;
    }

  }

  /**
   * Passowrd Confirm .
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   *   Request.
   *
   * @return \Zend\Diactoros\Response\JsonResponse
   *
   *   Json Response
   */
  public function passwordConfirm(Request $request) {

    try {

      $pass = $request->request->get('pass');
      /** @var \Drupal\user\Entity\User $user */
      if ($this->user) {
        $this->user->setPassword($pass);
        $this->user->set('field_social_auth_password', TRUE);
        $this->user->save();
        return new JsonResponse('success');
      }
    }
    catch (EntityStorageException $e) {
    }

    return new JsonResponse('failed');

  }

}
