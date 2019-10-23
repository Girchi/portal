<?php


namespace Drupal\girchi_donations\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;


/**
 * Form controller for Regular donation delete forms.
 *
 * @ingroup girchi_donations
 */
class RegularDonationDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * Returns the question to ask the user.
   *
   * @return void The form question. The page title will be set to this value.
   *   The form question. The page title will be set to this value.
   */
  public function getQuestion() {
    parent::getQuestion();
    // TODO: Implement getQuestion() method.
  }

  /**
   * Returns the route to go to if the user cancels the action.
   *
   * @return void A URL object.
   *   A URL object.
   */
  public function getCancelUrl() {
    parent::getCancelUrl();
    // TODO: Implement getCancelUrl() method.
  }

}
