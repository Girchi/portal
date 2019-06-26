<?php

namespace Drupal\girchi_utils\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * UpdatePageViewsForm.
 */
class UpdatePageViewsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'update_page_views_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['update'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update node views'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->execute();

    $batch = [
      'title' => t('Updating article...'),
      'operations' => [
        [
          '\Drupal\girchi_utils\UpdatePageViews::updateViews',
          [$nids],
        ],
      ],
      'finished' => '\Drupal\girchi_utils\UpdatePageViews::updateViewsFinishedCallback',
    ];

    batch_set($batch);
  }

}
