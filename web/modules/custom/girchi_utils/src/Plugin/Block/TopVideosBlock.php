<?php

namespace Drupal\girchi_utils\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\node\Entity\Node;

/**
 * Provides a 'Videos block' block.
 *
 * @Block(
 *  id = "top_videos_block",
 *  admin_label = @Translation("Videos block"),
 * )
 */
class TopVideosBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $em = \Drupal::entityTypeManager();

    /** @var \Drupal\node\Entity\NodeStorage $node_storage */
    $node_storage = $em->getStorage('node');
    $last_published_videos = $node_storage->getQuery()
      ->condition('type', 'article')
      ->condition('status', 1)
      ->condition('field_is_video', 1)
      ->sort('created', "DESC")
      ->range(0, 10)
      ->execute();

    if (!empty($last_published_videos)) {

      $top_videos = Node::loadMultiple($last_published_videos);
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
