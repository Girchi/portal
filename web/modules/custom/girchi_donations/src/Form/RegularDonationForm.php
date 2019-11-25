<?php

namespace Drupal\girchi_donations\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Regular donation edit forms.
 *
 * @ingroup girchi_donations
 */
class RegularDonationForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\girchi_donations\Entity\RegularDonation */
    $form = parent::buildForm($form, $form_state);

    // $entity = $this->entity;
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
        drupal_set_message($this->t('Created the %label Regular donation.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Regular donation.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.regular_donation.canonical', ['regular_donation' => $entity->id()]);
  }

}
