<?php

namespace Drupal\girchi_utils\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'TopTopicsBlock' block.
 *
 * @Block(
 *  id = "top_topics_block",
 *  admin_label = @Translation("Top topics block"),
 * )
 */
class TopTopicsBlock extends BlockBase implements ContainerFactoryPluginInterface {
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
    $slider_topics_num = 5;

    /** @var \Drupal\node\Entity\NodeStorage $node_storage */
    $node_storage = $em->getStorage('node');
    $last_published_nodes = $node_storage->getQuery()
      ->condition('type', 'article')
      ->condition('status', 1)
      ->sort('created', "DESC")
      ->range(0, 10)
      ->execute();

    if (!empty($last_published_nodes)) {
      $last_published_nodes_ent = $node_storage->loadMultiple($last_published_nodes);
      krsort($last_published_nodes_ent);
      $slider_topics = array_slice($last_published_nodes_ent, 0, $slider_topics_num);
      $bottom_topics = array_slice($last_published_nodes_ent, 5, 2);

      return [
        '#theme' => 'top_topics',
        '#slider_topics' => $slider_topics,
        '#bottom_topics' => $bottom_topics,
      ];
    }
    else {
      return [
        '#theme' => 'top_topics',
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
