<?php

namespace Drupal\girchi_utils\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal;

/**
 * Provides a 'CategoryFilterBlockMobile' block.
 *
 * @Block(
 *  id = "category_filter_block_mobile",
 *  admin_label = @Translation("Category filter block mobile"),
 * )
 */
class CategoryFilterBlockMobile extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $categories_tree = Drupal::service('girchi_utils.taxonomy_term_tree')->load('news_categories');
    $current_category = \Drupal::request()->query->get('category');
    return [
      '#theme' => 'categories_block_mobile',
      '#categories' => $categories_tree,
      '#current_category' => $current_category,
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Set block cache max age 3 hours and then invalidate.
    return 10800;
  }

}
