<?php

namespace Drupal\girchi_sms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Girchi Sms form.
 */
class MessageForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'girchi_sms_message';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#required' => TRUE,
    ];
    $form['regions'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'taxonomy_term',
      '#title' => $this->t('Regions'),
      '#description' => $this->t('Select Regions.'),
      '#tags' => TRUE,
      '#selection_settings' => [
        'target_bundles' => ['regions'],
      ],
      '#weight' => '0',
    ];
    $form['badges'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'taxonomy_term',
      '#title' => $this->t('Badges'),
      '#description' => $this->t('Select Badges.'),
      '#tags' => TRUE,
      '#selection_settings' => [
        'target_bundles' => ['badges'],
      ],
      '#weight' => '0',
    ];
    $form['id_number'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('პირადი ნომერი შევსებულია'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (mb_strlen($form_state->getValue('message')) < 5) {
      $form_state->setErrorByName('name', $this->t('Message should be at least 5 characters.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $message = $form_state->getValue('message');
    $regions = $form_state->getValue('regions');
    $badges = $form_state->getValue('badges');
    $idNumber = $form_state->getValue('id_number');
    $options = [
      "message" => $message,
      "regions" => $regions,
      "badges" => $badges,
      "idNumber" => $idNumber,
    ];
    /** @var \Drupal\girchi_sms\SmsSender $date */
    $smsService = \Drupal::service('girchi_sms.sms_sender');
    $res = $smsService->sendMultipleSms($options);
    $res = json_decode($res);
    if ($res->Success) {
      $this->messenger()->addStatus($this->t('The message has been sent.'));
      $this->logger('girchi_sms')->info($res->Message);
    }
    else {
      $this->messenger()->addError($this->t('Failed to send messages.'));
      $this->logger('girchi_sms')->error($res->Message);
    }
  }

}
