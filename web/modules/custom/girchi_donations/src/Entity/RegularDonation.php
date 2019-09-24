<?php

namespace Drupal\girchi_donations\Entity;

use Carbon\Carbon;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Regular donation entity.
 *
 * @ingroup girchi_donations
 *
 * @ContentEntityType(
 *   id = "regular_donation",
 *   label = @Translation("Regular donation"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\girchi_donations\RegularDonationListBuilder",
 *     "views_data" =
 *   "Drupal\girchi_donations\Entity\RegularDonationViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\girchi_donations\Form\RegularDonationForm",
 *       "add" = "Drupal\girchi_donations\Form\RegularDonationForm",
 *       "edit" = "Drupal\girchi_donations\Form\RegularDonationForm",
 *       "delete" = "Drupal\girchi_donations\Form\RegularDonationDeleteForm",
 *     },
 *     "access" =
 *   "Drupal\girchi_donations\RegularDonationAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\girchi_donations\RegularDonationHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "regular_donation",
 *   admin_permission = "administer regular donation entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "transid" = "trans_id",
 *     "uid" = "user_id",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/regular_donation/{regular_donation}",
 *     "add-form" = "/admin/structure/regular_donation/add",
 *     "edit-form" =
 *   "/admin/structure/regular_donation/{regular_donation}/edit",
 *     "delete-form" =
 *   "/admin/structure/regular_donation/{regular_donation}/delete",
 *     "collection" = "/admin/structure/regular_donation",
 *   },
 *   field_ui_base_route = "regular_donation.settings"
 * )
 */
class RegularDonation extends ContentEntityBase implements RegularDonationInterface {

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
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {

    if (empty($this->get('next_payment_date')->value)) {
      $today = Carbon::now();
      $day = $today->format('j');
      $hour = $today->format('G');
      $month = $today->format('n');
      $year = $today->format('Y');
      $payment_day = $this->get('payment_day')->value;
      $frequency = $this->get('frequency')->value;
      if ($payment_day > $day) {
        $month += $frequency;
        $payment_date = Carbon::createFromDate($year, $month, $payment_day);
        $date = $payment_date->toDateTime();
        $date->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
        $string = $date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT);
        $this->set('next_payment_date', $string);
      }
      elseif ($payment_day <= $day) {
        if ($hour < 17 && $payment_day == $day) {
          $payment_date = Carbon::createFromDate($year, $month, $payment_day);
        }
        else {
          $payment_date = Carbon::createFromDate($year, (int) $month + $frequency, $payment_day);
        }
        $date = $payment_date->toDateTime();
        $date->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
        $string = $date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT);
        $this->set('next_payment_date', $string);
      }
    }
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
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * Getter method for transaction ID.
   *
   * @return mixed
   *   Value.
   */
  public function getTransactionId() {
    return $this->get('trans_id')->value;
  }

  /**
   * Getter method for client ID.
   *
   * @return mixed
   *   Value.
   */
  public function getClientId() {
    return $this->get('client_id')->value;
  }

  /**
   * Gets the status of donation entity.
   *
   * @return string
   *   Status of regular donation entity.
   */
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * Updates Status.
   *
   * @param string $status
   *   Status of donation.
   *
   * @return \Drupal\girchi_donations\Entity\RegularDonation
   *   Drupal donation Entity.
   */
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
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

    // Client id.
    $fields['card_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Card ID'))
      ->setDescription(t('Card id generated for saving card.'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Regular donation entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
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

    // Aim ID.
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

    // Politician ID.
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

    // Type.
    $fields['type'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Type of regular donation'))
      ->setDescription(t('Type of regular donation'));

    // Amount.
    $fields['amount'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Amount of money'))
      ->setDescription(t('Amount of money'));

    // Frequency.
    $fields['frequency'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Frequency'))
      ->setDescription(t('Frequency of regular donation'));

    // Payment day.
    $fields['payment_day'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Day of payment'))
      ->setDescription(t('Day of payment'));

    // STATUS.
    $fields['status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Donation status'))
      ->setDescription(t('Donation status.'))
      ->setDefaultValue('INITIAL');

    // Next payment date.
    $fields['next_payment_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Next payment date'))
      ->setDescription(t('Date of next payment'))
      ->setSettings([
        'datetime_type' => 'date',
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'settings' => [
          'format_type' => 'medium',
        ],
        'weight' => 14,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 14,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
