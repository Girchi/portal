<?php

namespace Drupal\girchi_chatbot_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for Chatbot Integration routes.
 */
class GirchiChatbotIntegrationController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Builds the response.
   */
  public function checkCode($code) {

    $user_manager = $this->entityTypeManager->getStorage('user');

    $users = $user_manager->loadByProperties([
      'field_bot_integration_code' => $code,
      'field_connected_with_bot' => 0,
    ]);

    if (empty($users)) {
      $message = 'მემგონი რაღაც შეგეშალა, კარგად გადაამოწმე და თავიდან გამომიგზავნე კოდი :)';
    }
    else {
      /**
       * @var $user \Drupal\user\Entity\User
       */
      $user = array_shift(array_values($users));
      $user->set('field_connected_with_bot', 1);
      $user->save();

      $message = sprintf('ეგაა! წარმატებით დავუკავშირდი პორტალზე შენს ანგარიშს - %s', $user->getDisplayName());
    }

    $response = [
      'messages' => [
        'text' => $message,
      ],
    ];
    return JsonResponse::create($response);
  }

}
