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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->currentUser = $container->get('current_user');
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
    $form['government_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Government ID'),
      '#maxlength' => 11,
      '#size' => 11,
      '#weight' => '0',
    ];
    $form['firstname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Firstname'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '1',
    ];
    $form['lastname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Lastname'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '2',
    ];
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#weight' => '3',
    ];
    $form['phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone'),
      '#weight' => '4',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Register'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
