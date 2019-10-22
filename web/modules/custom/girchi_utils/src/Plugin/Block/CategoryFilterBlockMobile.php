<?php

namespace Drupal\girchi_utils\Plugin\Block;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Block\BlockBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\girchi_utils\TaxonomyTermTree;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'CategoryFilterBlockMobile' block.
 *
 * @Block(
 *  id = "category_filter_block_mobile",
 *  admin_label = @Translation("Category filter block mobile"),
 * )
 */
class CategoryFilterBlockMobile extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * Taxonomy term tree.
   *
   * @var \Drupal\girchi_utils\TaxonomyTermTree
   */
  protected $taxonomyTermTree;

  /**
   * Request Stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * CategoryFilterBlockMobile constructor.
   *
   * @param array $configuration
   *   Array of configuration.
   * @param int $plugin_id
   *   Plugin id.
   * @param string $plugin_definition
   *   Plugin id.
   * @param \Drupal\girchi_utils\TaxonomyTermTree $taxonomyTermTree
   *   Taxonomy term tree.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Request stack.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    TaxonomyTermTree $taxonomyTermTree,
    RequestStack $request) {
    parent::__construct(
    $configuration,
    $plugin_id, $plugin_definition);
    $this->taxonomyTermTree = $taxonomyTermTree;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('girchi_utils.taxonomy_term_tree'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $categories_tree = $this->taxonomyTermTree->load('news_categories');
    $current_category = $this->request->getCurrentRequest()->get('category');
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
