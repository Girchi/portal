<?php

namespace Drupal\girchi_utils\Validate;

use Drupal\Core\Form\FormStateInterface;

/**
 * ValidationConstraint.
 */
class ValidationConstraint {

  /**
   * Function for validation.
   */
  public static function validate(array &$element, FormStateInterface $formState, array &$form) {
    if ($element['#webform_key'] == 'investment_amount') {
      $webform_field = ($form['elements']['investment_amount']);
      $is_parent = $formState->getValue('i_am_a_parent');
      $investment_amount = $formState->getValue('investment_amount');
      if ($is_parent == TRUE && $investment_amount < 10000) {
        $formState->setError($webform_field, t('Minimum amount of investment for parents must be equal to 10K dollar.'));
      }
    }
  }

}
