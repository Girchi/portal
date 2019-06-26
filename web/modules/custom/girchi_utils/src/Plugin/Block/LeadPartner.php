<?php

namespace Drupal\girchi_utils\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\field\Entity\FieldConfig;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides a 'LeadPartner' block.
 *
 * @Block(
 *  id = "lead_partner",
 *  admin_label = @Translation("Lead partner"),
 * )
 */
class LeadPartner extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $query = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', 'lead_partner')
      ->condition('status', 1)
      ->sort('field_weight', "ASC")
      ->range(0, 10);

    $tids = $query->execute();
    $top_partners = Term::loadMultiple($tids);
    $final_partners = [];
    foreach ($top_partners as $partner) {
      $name = $partner->getName();
      $weight = $partner->get('field_weight')->value;
      $donation = $partner->get('field_donated_amount')->value;
      $field_image_entity = $partner->get('field_image')->entity;
      if ($field_image_entity) {
        $img = $field_image_entity->getFileUri();
      }
      else {
        $field_info = FieldConfig::loadByName('taxonomy_term', 'lead_partner', 'field_image');
        $image_uuid = $field_info->getSetting('default_image')['uuid'];
        $image = \Drupal::service('entity.repository')->loadEntityByUuid('file', $image_uuid);
        $img = $image->getFileUri();
      }
      $final_partners[] = [
        'name' => $name,
        "weight" => $weight,
        'donation' => $donation,
        'img' => $img,
      ];
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

}
