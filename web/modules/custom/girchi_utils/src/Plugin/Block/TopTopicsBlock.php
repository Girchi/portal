<?php

namespace Drupal\girchi_utils\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\node\Entity\Node;

/**
 * Provides a 'TopTopicsBlock' block.
 *
 * @Block(
 *  id = "top_topics_block",
 *  admin_label = @Translation("Top topics block"),
 * )
 */
class TopTopicsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $em = \Drupal::entityTypeManager();
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

      $last_published_nodes_ent = Node::loadMultiple($last_published_nodes);
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
