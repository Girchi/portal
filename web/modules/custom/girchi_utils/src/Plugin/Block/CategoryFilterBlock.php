<?php

namespace Drupal\girchi_utils\Plugin\Block;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\girchi_utils\TaxonomyTermTree;
use Drupal\node\NodeInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'CategoryFilterBlock' block.
 *
 * @Block(
 *  id = "category_filter_block",
 *  admin_label = @Translation("Category filter block"),
 * )politician_rating_block
 */
class CategoryFilterBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Request Stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * Route Matcher.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $router;

  /**
   * Taxonomy term tree.
   *
   * @var \Drupal\girchi_utils\TaxonomyTermTree
   */
  protected $taxonomyTermTree;

  /**
   * CategoryFilterBlock constructor.
   *
   * @param array $configuration
   *   Array of configuration.
   * @param int $plugin_id
   *   Plugin id.
   * @param string $plugin_definition
   *   Plugin definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Request stack.
   * @param \Drupal\Core\Routing\RouteMatchInterface $router
   *   Current route matcher.
   * @param \Drupal\girchi_utils\TaxonomyTermTree $taxonomyTermTree
   *   Taxonomy term tree.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RequestStack $request,
    RouteMatchInterface $router,
    TaxonomyTermTree $taxonomyTermTree) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->request = $request;
    $this->router = $router;
    $this->taxonomyTermTree = $taxonomyTermTree;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('current_route_match'),
      $container->get('girchi_utils.taxonomy_term_tree')

    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $categories_tree = $this->taxonomyTermTree->load('news_categories');
    $current_category = $this->request->getCurrentRequest()->get('category');

    $node = $this->router->getParameter('node');
    if ($node instanceof NodeInterface) {
      if (!empty($node->get('field_category')[0])) {
        $current_category = $node->get('field_category')[0]->entity->id();
      }
    }

    return [
      '#theme' => 'categories_block',
      '#categories' => $categories_tree,
      '#current_category' => $current_category,
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['url']);
  }

}
