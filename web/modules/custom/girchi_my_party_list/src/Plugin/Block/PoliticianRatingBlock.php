<?php

namespace Drupal\girchi_my_party_list\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'PoliticianRatingBlock' block.
 *
 * @Block(
 *  id = "politician_rating_block",
 *  admin_label = @Translation("Politician rating block"),
 * )
 */
class PoliticianRatingBlock extends BlockBase implements ContainerFactoryPluginInterface {


  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManager $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
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
   * Builds and returns the renderable array for this block plugin.
   *
   * If a block should not be rendered because it has no content, then this
   * method must also ensure to return no content: it must then only return an
   * empty array, or an empty array with #cache set (with cacheability metadata
   * indicating the circumstances for it being empty).
   *
   * @return array
   *   A renderable array representing the content of the block.
   *
   * @see \Drupal\block\BlockViewBuilder
   */
  public function build() {
    $politicians = [];
    $userStorage = $this->entityTypeManager->getStorage('user');
    $result = $userStorage->getQuery()
      ->condition('field_politician', TRUE, '=')
      ->condition('field_rating_in_party_list', 0, '>')
      ->range(0, 4)
      ->sort('field_rating_in_party_list', 'ASC')
      ->execute();
    $users = $userStorage->loadMultiple($result);
    foreach ($users as $user) {
      $first_name = $user->get('field_first_name')->value;
      $last_name = $user->get('field_last_name')->value;
      $img_url = $user->get('user_picture')->entity->getFileUri();
      $rating = $user->get('field_rating_in_party_list')->value;
      $political_ged = $user->get('field_political_ged')->value;
      $user_id = $user->id();
      $politicians[] = [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'img_uri' => $img_url,
        'rating' => $rating,
        'political_ged' => $political_ged,
        'uid' => $user_id,
      ];
    }
    return [
      '#theme' => 'politician_rating_block',
      '#politicians' => $politicians,
    ];
  }

}
