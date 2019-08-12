<?php

namespace Drupal\girchi_donations\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\UserInterface;

/**
 * Defines the Donation entity.
 *
 * @ingroup girchi_donations
 *
 * @ContentEntityType(
 *   id = "donation",
 *   label = @Translation("Donation"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\girchi_donations\.DonationListBuilder",
 *     "views_data" = "Drupal\girchi_donations\Entity\DonationViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\girchi_donations\Form\DonationForm",
 *       "add" = "Drupal\girchi_donations\Form\DonationForm",
 *       "edit" = "Drupal\girchi_donations\Form\DonationForm",
 *       "delete" = "Drupal\girchi_donations\Form\DonationDeleteForm",
 *     },
 *     "access" = "Drupal\girchi_donations\DonationAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\girchi_donations\DonationHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "donation",
 *   admin_permission = "administer donation entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "trans_id" = "trans_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/donation/{donation}",
 *     "add-form" = "/admin/structure/donation/add",
 *     "edit-form" = "/admin/structure/donation/{donation}/edit",
 *     "delete-form" = "/admin/structure/donation/{donation}/delete",
 *     "collection" = "/admin/structure/donation",
 *   },
 *   field_ui_base_route = "donation.settings"
 * )
 */
class Donation extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }


  /**
   * Returns the transaction id.
   *
   * @return string
   *   The transaction id.
   */
  public function getDonationId() {
    return $this->get('trans_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUser() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setUser(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }


  /**
   * {@inheritdoc}
   */
    public function getAim() {
    return $this->get('aim_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setAim(Term $term) {
    $this->set('aim_id', $term->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPolitician() {
    return $this->get('politician_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setPolitician(UserInterface $politician) {
    $this->set('politician', $politician->id());
    return $this;
  }

  /**
   * Updates Status.
   *
   * @param boolean $bool
   *   TRUE or FALSE.
   *
   * @return \Drupal\girchi_donations\Entity\Donation
   *   Drupal donation Entity.
   */
  public function SetDonationHasAim($bool) {
    $this->set('aim_donation', $bool);
    return $this;
  }

  /**
   * Updates Status.
   *
   * @param boolean $bool
   *   TRUE or FALSE.
   *
   * @return \Drupal\girchi_donations\Entity\Donation
   *   Drupal donation Entity.
   */
  public function SetDonationHasPolitician($bool) {
    $this->set('aim_donation', $bool);
    return $this;
  }

  /**
   * Updates Status.
   *
   * @param string $status
   *   Status of donation.
   *
   * @return \Drupal\girchi_donations\Entity\Donation
   *   Drupal donation Entity.
   */
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
  }

  /**
   * Gets the changed time field.
   *
   * @return string
   *   Changed timestamp donation entity.
   */
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * Updates Status.
   *
   * @param integer $amount
   *   Status of donation.
   *
   * @return \Drupal\girchi_donations\Entity\Donation
   *   Drupal donation Entity.
   */
  public function setAmount($amount) {
    $this->set('amount', $amount);
    return $this;
  }

  /**
   * Get amount of donation
   *
   * @return integer
   *   Amount of donation.
   */
  public function getAmount() {
    return $this->get('amount')->value;
  }



  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['trans_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Transaction ID'))
      ->setDescription(t('Transaction id generated by merchant'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Donation entity.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    //STATUS
    $fields['status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Donation status'))
      ->setDescription(t('Donation status.'))
      ->setDefaultValue('INITIAL');

    //AIM DONATIONS
    $fields['aim_donation'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Aim donation'))
      ->setDescription(t('A boolean indicating that this donation was made for special aim.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['aim_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('ID of aim taxonomy term'))
      ->setDescription(t('ID of aim taxonomy term.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Politician donation
    $fields['politician_donation'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Politician donation'))
      ->setDescription(t('A boolean indicating that this donation was made to politician.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['politician_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('ID of aim politician'))
      ->setDescription(t('ID of politician.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    //Amount
    $fields['amount'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Amount of money'))
      ->setDescription(t('Amount of money'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
