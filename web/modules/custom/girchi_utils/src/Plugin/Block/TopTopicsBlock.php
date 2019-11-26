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

    /** @var \Drupal\node\NodeStorage $node_storage */
    $node_storage = $em->getStorage('node');
    $last_published_nodes = $node_storage->getQuery()
      ->condition('type', 'article')
      ->condition('status', 1)
      ->condition('field_featured_on_slider', 1)
      ->sort('field_published_date', "DESC")
      ->sort('created', 'DESC')
      ->range(0, 10)
      ->execute();

    $node_count = count($last_published_nodes);
    if (!empty($last_published_nodes) && $node_count == 10) {

      /** @var \Drupal\node\NodeStorage $node_storage */
      $last_published_nodes_ent = $node_storage->loadMultiple($last_published_nodes);
      $slider_topics = array_slice($last_published_nodes_ent, 0, $slider_topics_num);
      $bottom_topics = array_slice($last_published_nodes_ent, 5, 2);

      return [
        '#theme' => 'top_topics',
        '#slider_topics' => $slider_topics,
        '#bottom_topics' => $bottom_topics,
      ];
    }
    elseif (!empty($last_published_nodes) && $node_count < 10) {

      $ids = '(';
      $ids .= implode(',', array_keys($last_published_nodes));
      $ids .= ')';

      $needed_amount = 10 - $node_count;
      $nodes = $node_storage->getQuery()
        ->condition('type', 'article')
        ->condition('status', 1)
        ->condition('nid', $ids, 'NOT IN')
        ->sort('field_published_date', "DESC")
        ->sort('created', 'DESC')
        ->range(0, $needed_amount)
        ->execute();

      /** @var \Drupal\node\NodeStorage $node_storage */
      $last_published_nodes_ent = $node_storage->loadMultiple(array_merge($last_published_nodes, $nodes));
      $slider_topics = array_slice($last_published_nodes_ent, 0, $slider_topics_num);
      $bottom_topics = array_slice($last_published_nodes_ent, 5, 2);

      return [
        '#theme' => 'top_topics',
        '#slider_topics' => $slider_topics,
        '#bottom_topics' => $bottom_topics,
      ];

    }
    else {
      $last_published_nodes = $node_storage->getQuery()
        ->condition('type', 'article')
        ->condition('status', 1)
        ->sort('field_published_date', "DESC")
        ->sort('created', 'DESC')
        ->range(0, 10)
        ->execute();

      $last_published_nodes_ent = $node_storage->loadMultiple($last_published_nodes);
      $slider_topics = array_slice($last_published_nodes_ent, 0, $slider_topics_num);
      $bottom_topics = array_slice($last_published_nodes_ent, 5, 2);

      return [
        '#theme' => 'top_topics',
        '#slider_topics' => $slider_topics,
        '#bottom_topics' => $bottom_topics,
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
