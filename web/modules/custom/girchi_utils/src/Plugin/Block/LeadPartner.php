<?php

namespace Drupal\girchi_utils\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'LeadPartner' block.
 *
 * @Block(
 *  id = "lead_partner",
 *  admin_label = @Translation("Lead partner"),
 * )
 */
class LeadPartner extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
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
   * {@inheritdoc}
   */
  public function build() {
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $user_storage = $this->entityTypeManager->getStorage('user');

    $tids = $term_storage->getQuery()
      ->condition('vid', 'lead_partner')
      ->condition('status', 1)
      ->sort('field_weight', "ASC")
      ->range(0, 10)
      ->execute();

    $top_partners = $term_storage->loadMultiple($tids);
    $final_partners = [];
    foreach ($top_partners as $partner) {
      if ($partner->get('field_partner')->getvalue()) {
        $uid = $partner->get('field_partner')->getvalue()[0]['target_id'];
        /** @var \Drupal\user\Entity\User $user */
        $user = $user_storage->load($uid);
        $user_name = $user->get('field_first_name')->value;
        $user_surname = $user->get('field_last_name')->value;

        $weight = $partner->get('field_weight')->value;
        $donation = $partner->get('field_donated_amount')->value;

        if ($user->get('user_picture')->entity) {
          $profilePictureEntity = $user->get('user_picture')->entity;
          $profilePicture = $profilePictureEntity->getFileUri();
        }
        else {
          $profilePicture = NULL;
        }

        $final_partners[] = [
          'uid' => $uid,
          'user_name' => $user_name,
          'user_surname' => $user_surname,
          "weight" => $weight,
          'donation' => $donation,
          'img' => $profilePicture,
        ];
      }
    }
    return [
      '#theme' => 'lead_partners',
      '#leadPartner' => $final_partners,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['taxonomy_term_list:lead_partner']);
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

}
