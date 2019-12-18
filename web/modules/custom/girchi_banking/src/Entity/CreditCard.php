<?php

namespace Drupal\girchi_banking\Entity;

use Carbon\Carbon;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Credit card entity.
 *
 * @ingroup girchi_banking
 *
 * @ContentEntityType(
 *   id = "credit_card",
 *   label = @Translation("Credit card"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\girchi_banking\CreditCardListBuilder",
 *     "views_data" = "Drupal\girchi_banking\Entity\CreditCardViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\girchi_banking\Form\CreditCardForm",
 *       "add" = "Drupal\girchi_banking\Form\CreditCardForm",
 *       "edit" = "Drupal\girchi_banking\Form\CreditCardForm",
 *       "delete" = "Drupal\girchi_banking\Form\CreditCardDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\girchi_banking\CreditCardHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\girchi_banking\CreditCardAccessControlHandler",
 *   },
 *   base_table = "credit_card",
 *   translatable = FALSE,
 *   permission_granularity = "bundle",
 *   admin_permission = "administer credit card entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/credit_card/{credit_card}",
 *     "add-form" = "/admin/structure/credit_card/add",
 *     "edit-form" = "/admin/structure/credit_card/{credit_card}/edit",
 *     "delete-form" = "/admin/structure/credit_card/{credit_card}/delete",
 *     "collection" = "/admin/structure/credit_card",
 *   },
 *   field_ui_base_route = "credit_card.settings"
 * )
 */
class CreditCard extends ContentEntityBase implements CreditCardInterface {

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
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime($format = FALSE) {
    if ($format) {
      return Carbon::createFromTimestamp($this->get('created')->value)->format('Y/m/d');
    }
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
   * Function for getting generated id for execution.
   *
   * @return string
   *   TBC id;
   */
  public function getTbcId() {
    return $this->get('tbc_id')->value;
  }

  /**
   * Function for getting expiry.
   *
   * @param bool $format
   *   Format expiry e.g '03/22'.
   *
   * @return string
   *   expiry;
   */
  public function getExpiry($format = FALSE) {
    if ($format) {
      return substr_replace($this->get('expiry')->value, '/', 2, 0);
    }
    return $this->get('expiry')->value;
  }

  /**
   * Function for setting expiry.
   *
   * @param string $expiry
   *   Expiry string e.g 0322.
   *
   * @return \Drupal\girchi_banking\Entity\CreditCard
   *   Credit card.;
   */
  public function setExpiry($expiry) {
    $this->set('expiry', $expiry);
    return $this;

  }

  /**
   * Function for getting type of credit card.d.
   *
   * @return string
   *   VISA OR Mastercard
   */
  public function getType() {
    return $this->get('type')->value;
  }

  /**
   * Function for setting credit card type.
   *
   * @param string $type
   *   Type MC or Visa.
   *
   * @return \Drupal\girchi_banking\Entity\CreditCard
   *   Credit card.
   *
   * @throws \Exception
   */
  public function setType($type) {
    if (preg_match('(MC|VISA)', $type) === 1) {
      $this->set('type', $type);
      return $this;
    }

    throw new \Exception(sprintf('Invalid Type for credit card type: %s', $type));
  }

  /**
   * Function for getting last 4 digits.
   *
   * @return string
   *   last 4 digits.
   */
  public function getDigits() {
    return $this->get('digits')->value;
  }

  /**
   * Function for setting last 4 digits.
   *
   * @param string $digits
   *   Digits.
   *
   * @return \Drupal\girchi_banking\Entity\CreditCard
   *   Credit card.
   */
  public function setDigits($digits) {
    $this->set('digits', $digits);
    return $this;
  }

  /**
   * Function for getting credit card status.
   *
   * @return string
   *   Status.
   */
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * Function for setting credit card status.
   *
   * @param string $status
   *   Status.
   *
   * @return \Drupal\girchi_banking\Entity\CreditCard
   *   Credit Card.
   *
   * @throws \Exception
   */
  public function setStatus($status) {
    if (preg_match('(OK|INITIAL|ACTIVE)', $status) === 1) {
      $this->set('status', $status);
      return $this;
    }

    throw new \Exception(sprintf('Invalid Type for credit card status: %s', $status));
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Credit card entity.'))
      ->setRevisionable(TRUE)
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

    $fields['trans_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Transaction ID'))
      ->setDescription(t('Transaction id generated by merchant'))
      ->setReadOnly(TRUE);

    $fields['tbc_id'] = BaseFieldDefinition::create('string')
      ->setLabel('TBC id')
      ->setDescription('Card id for TBC')
      ->setReadOnly(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Credit card entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['digits'] = BaseFieldDefinition::create('string')
      ->setLabel('Digits')
      ->setDescription('Last 4 digits of credit card')
      ->setSettings(['max_length' => 4]);

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel('Type')
      ->setDescription('Type of credit card VISA/MC');

    $fields['status'] = BaseFieldDefinition::create('string')
      ->setLabel('Status')
      ->setDescription('Status of credit card INITIAL/OK/FAILED');

    $fields['expiry'] = BaseFieldDefinition::create('string')
      ->setLabel('Expiry')
      ->setDescription('Card expiry date');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
