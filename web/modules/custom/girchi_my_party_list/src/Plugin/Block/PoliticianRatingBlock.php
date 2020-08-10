<?php

namespace Drupal\girchi_my_party_list\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\girchi_users\GEDHelperService;
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
   * ConfigFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Ged Formatter.
   *
   * @var \Drupal\girchi_users\GEDHelperService
   */
  protected $gedFormatter;

  /**
   * Construct.
   *
   * @param array $configuration
   *   Configuration.
   * @param int $plugin_id
   *   Plugin id.
   * @param string $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   ConfigFactory.
   * @param \Drupal\girchi_users\GEDHelperService $gedFormatter
   *   Ged formatter.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ConfigFactory $configFactory, GEDHelperService $gedFormatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $configFactory;
    $this->gedFormatter = $gedFormatter;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('girchi_users.ged_helper')

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
    $number_of_politicians = $this->configFactory->get('om_site_settings.site_settings')->get('number_of_politicians');

    $range_value = $number_of_politicians ? $number_of_politicians : 5;

    $politicians = [];
    $userStorage = $this->entityTypeManager->getStorage('user');
    $result = $userStorage->getQuery()
      ->condition('field_politician', TRUE, '=')
      ->condition('field_rating_in_party_list', 0, '>')
      ->range(0, $range_value)
      ->sort('field_rating_in_party_list', 'ASC')
      ->execute();
    $users = $userStorage->loadMultiple($result);
    foreach ($users as $user) {
      $first_name = $user->get('field_first_name')->value;
      $last_name = $user->get('field_last_name')->value;
      $rating = $user->get('field_rating_in_party_list')->value;
      $political_ged = $this->gedFormatter->getFormattedGed($user->get('field_political_ged')->value);
      $user_id = $user->id();

      if ($user->get('user_picture')->entity) {
        $img_uri = $user->get('user_picture')->entity->getFileUri();
      }
      else {
        $img_uri = NULL;
      }

      $politicians[] = [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'img_uri' => $img_uri,
        'rating' => $rating,
        'political_ged' => $political_ged,
        'uid' => $user_id,
      ];
    }

    return [
      '#theme' => 'politician_rating_block',
      '#politicians' => $politicians,
      '#block_settings' => NULL,
    ];

  }

}
