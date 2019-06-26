<?php

namespace Drupal\girchi_ged_transactions;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of GED transaction entities.
 *
 * @ingroup girchi_ged_transactions
 */
class GedTransactionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('GED transaction ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\girchi_ged_transactions\Entity\GedTransaction */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.ged_transaction.edit_form',
      ['ged_transaction' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
