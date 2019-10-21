<?php

namespace Drupal\girchi_utils\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Videos block' block.
 *
 * @Block(
 *  id = "top_videos_block",
 *  admin_label = @Translation("Videos block"),
 * )
 */
class TopVideosBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * FrontNewsBlock constructor.
   *
   * @param array $configuration
   *   Array of configuration.
   * @param int $plugin_id
   *   Plugin id.
   * @param string $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $em = $this->entityTypeManager;

    /** @var \Drupal\node\NodeStorage $node_storage */
    $node_storage = $em->getStorage('node');
    $last_published_videos = $node_storage->getQuery()
      ->condition('type', 'article')
      ->condition('status', 1)
      ->condition('field_is_video', 1)
      ->sort('created', "DESC")
      ->range(0, 10)
      ->execute();

    if (!empty($last_published_videos)) {

      $top_videos = $node_storage->loadMultiple($last_published_videos);
      krsort($top_videos);

      return [
        '#theme' => 'top_videos',
        '#top_videos' => $top_videos,

      ];
    }
    else {
      return [
        '#theme' => 'top_videos',
      ];
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['node_list']);
  }

}
