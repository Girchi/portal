<?php

namespace Drupal\girchi_paypal\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PaypalSettingsForm.
 */
class PaypalSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'girchi_paypal.paypalsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'paypal_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('girchi_paypal.paypalsettings');
    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client id'),
      '#description' => $this->t('Add paypal client id'),
      '#maxlength' => 512,
      '#size' => 128,
      '#default_value' => $config->get('client_id'),
    ];
    $form['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client secret'),
      '#description' => $this->t('Add paypal client secret'),
      '#maxlength' => 512,
      '#size' => 128,
      '#default_value' => $config->get('client_secret'),
    ];
    $form['environment'] = [
      '#type' => 'select',
      '#title' => $this->t('Environment'),
      '#description' => $this->t('Select environment for paypal'),
      '#options' => ['production' => $this->t('production'), 'sandbox' => $this->t('sandbox')],
      '#size' => 5,
      '#default_value' => $config->get('environment'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('girchi_paypal.paypalsettings')
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('client_secret', $form_state->getValue('client_secret'))
      ->set('environment', $form_state->getValue('environment'))
      ->save();
  }

}
