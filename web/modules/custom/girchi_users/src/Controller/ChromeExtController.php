<?php

namespace Drupal\girchi_users\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 * Class ChromeExtController.
 */
class ChromeExtController extends ControllerBase {

  /**
   * ArrayObject definition.
   *
   * @var \ArrayObject
   */
  protected $containerNamespaces;

  /**
   * Database Connection.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $databaseConnection;

  /**
   * EntityTypeManager.
   *
   * @var Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->containerNamespaces = $container->get('container.namespaces');
    $instance->databaseConnection = $container->get('database');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * List.
   *
   * @return string
   *   Return list of users.
   */
  public function list() {
    $user = $this->entityTypeManager->getStorage('user');

    $uids = $user->loadMultiple();
    $users = [];

    $query_string = "SELECT user_id, provider_user_id FROM {social_auth} WHERE user_id IN (:ids[])";
    $query = $this->databaseConnection->query($query_string, [':ids[]' => array_keys($uids)]);
    $result = $query->fetchAll();

    if ($result) {
      foreach ($result as $item) {
        if ($user = $user->load($item->user_id)) {
          $name = $user->get('field_first_name')->getValue()[0]['value'];
          $name .= ' ' . $user->get('field_last_name')->getValue()[0]['value'];
          $users[] = [
            "fbid" => $item->provider_user_id,
            "fbhandle" => '',
            "name" => trim($name),
            'tags' => [],
          ];
        }
      }
    }

    return new JsonResponse($users);
  }

}
