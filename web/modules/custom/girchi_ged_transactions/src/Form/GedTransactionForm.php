<?php

namespace Drupal\girchi_ged_transactions\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for GED transaction edit forms.
 *
 * @ingroup girchi_ged_transactions
 */
class GedTransactionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\girchi_ged_transactions\Entity\GedTransaction */
    $form = parent::buildForm($form, $form_state);

    $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label GED transaction.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label GED transaction.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.ged_transaction.canonical', ['ged_transaction' => $entity->id()]);
  }

}
