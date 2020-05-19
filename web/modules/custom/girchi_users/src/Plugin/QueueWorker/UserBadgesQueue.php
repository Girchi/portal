<?php

namespace Drupal\girchi_users\Plugin\QueueWorker;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes UserBadge tasks.
 *
 * @QueueWorker(
 *   id = "user_badges_queue",
 *   title = @Translation("Processes user badges"),
 *   cron = {"time" = 60}
 * )
 */
class UserBadgesQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {
  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected  $entityTypeManager;
  /**
   * Json.
   *
   * @var \Drupal\Component\Serialization\Json
   */
  protected $json;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entityTypeManager,
                              Json $json) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->json = $json;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('serialization.json')
    );

  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $user = $this->entityTypeManager->getStorage('user')->load($data['uid']);
    /** @var \Drupal\Core\Field\FieldItemList $user_badges */
    $user_badges = $user->get('field_badges');
    $tid = $data['tid'];

    $appearance_array = [
      'visibility' => TRUE,
      'selected' => FALSE,
      'approved' => TRUE,
      'status_message' => '',
      'earned_badge' => TRUE,
    ];
    $value = $this->json->encode($appearance_array);

    if (!$user_badges->isEmpty()) {
      $user_badges_new = clone $user_badges;
      /** @var \Drupal\Core\Field\FieldItemList $badge_exists */
      $badge_exists = $user_badges_new->filter(static function ($user_badges) use ($tid) {
        return $tid == $user_badges->target_id;
      });

      if ($badge_exists->isEmpty()) {
        $user_badges->appendItem([
          'target_id' => $tid,
          'value' => $value,
        ]);
        $user->save();
      }
    }
    else {
      $user_badges->appendItem([
        'target_id' => $tid,
        'value' => $value,
      ]);
      $user->save();
    }

  }

}
