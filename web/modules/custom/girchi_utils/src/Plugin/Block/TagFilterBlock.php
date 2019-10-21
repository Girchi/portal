<?php

namespace Drupal\girchi_utils\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'TagFilterBlock' block.
 *
 * @Block(
 *  id = "tag_filter_block",
 *  admin_label = @Translation("Tag filter block"),
 * )
 */
class TagFilterBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
    $tags_tree = [];
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $query = $term_storage->getQuery()
      ->condition('vid', 'tags')
      ->condition('field_featured', '1')
      ->range(0, 10)
      ->condition('status', 1);

    $tids = $query->execute();
    if (!empty($tids) && count($tids) < 10) {
      $query = $term_storage->getQuery()
        ->condition('vid', 'tags')
        ->condition('tid', $tids, 'NOT IN')
        ->range(0, 10 - count($tids))
        ->condition('status', 1);
      $additional_tids = $query->execute();
      $tids = array_merge($tids, $additional_tids);
    }
    elseif (empty($tids)) {
      $query = $term_storage->getQuery()
        ->condition('vid', 'tags')
        ->range(0, 10)
        ->condition('status', 1);
      $tids = $query->execute();
    }

    if (!empty($tids)) {
      $terms = $term_storage->loadMultiple($tids);
      foreach ($terms as $term) {
        $tags_tree[] = ['tid' => $term->id(), 'name' => $term->getName()];
      }
    }

    return [
      '#theme' => 'tags_block',
      '#tags' => $tags_tree,
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['url.query_args']);
  }

}
