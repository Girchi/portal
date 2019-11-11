<?php

namespace Drupal\girchi_banking;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Credit card entities.
 *
 * @ingroup girchi_banking
 */
class CreditCardListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Credit card ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\girchi_banking\Entity\CreditCard $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.credit_card.edit_form',
      ['credit_card' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
