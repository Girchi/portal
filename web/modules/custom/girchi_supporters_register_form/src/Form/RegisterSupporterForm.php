<?php

namespace Drupal\girchi_supporters_register_form\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RegisterSupporterForm.
 */
class RegisterSupporterForm extends FormBase {

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * UserUtils service.
   *
   * @var Drupal\girchi_users\UsersUtils
   */
  protected $usersUtils;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->currentUser = $container->get('current_user');
    $instance->usersUtils = $container->get('girchi_users.utils');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'register_supporter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['registrator'] = [
      '#markup' => '<div class="registrator-box">Registrator: <strong>' . $this->currentUser->getDisplayName() . '</strong></div>',
    ];
    $form['gov_id'] = [
      '#type' => 'textfield',
      '#title' => 'Government ID',
      '#maxlength' => 11,
      '#size' => 11,
      '#weight' => '0',
      '#required' => 1,
    ];
    $form['firstname'] = [
      '#type' => 'textfield',
      '#title' => 'Firstname',
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '1',
      '#required' => 1,
    ];
    $form['lastname'] = [
      '#type' => 'textfield',
      '#title' => 'Lastname',
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '2',
      '#required' => 1,
    ];
    $form['email'] = [
      '#type' => 'email',
      '#title' => 'Email',
      '#weight' => '3',
      '#required' => 1,
    ];
    $form['phone'] = [
      '#type' => 'tel',
      '#title' => 'Phone',
      '#weight' => '4',
      '#required' => 1,
      '#default_value' => '+995',
    ];
    $form['ged_amount'] = [
      '#type' => 'number',
      '#title' => 'GED amount',
      '#weight' => '5',
      '#default' => 0,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Register',
      '#weight' => '100',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values = $form_state->getValues();

    if ($this->usersUtils->fieldIsTaken('field_personal_id', $values['gov_id'])) {
      $form_state->setErrorByName('gov_id', sprintf('Personal number %s is already used by other girchi.com user.', $values['gov_id']));
    }

    if ($this->usersUtils->fieldIsTaken('mail', $values['email'])) {
      $form_state->setErrorByName('email', sprintf('Email %s is already used by other girchi.com user.', $values['email']));
    }

    if ($this->usersUtils->fieldIsTaken('field_tel', $values['phone'])) {
      $form_state->setErrorByName('phone', sprintf('Phone number %s is already used by other girchi.com user.', $values['phone']));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
