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
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Referral entity.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $referral;

  /**
   * Messenger service.
   *
   * @var \Drupal\pathauto\MessengerInterface
   */
  protected $messenger;

  /**
   * State service.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * Number of total registered supporters by current user.
   *
   * @var int
   */
  private $registeredSupportersNumber;

  /**
   * Storage api key for current user to get total registered supporters number.
   *
   * @var int
   */
  private $registeredSupportersNumKey;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->currentUser = $container->get('current_user');
    $instance->usersUtils = $container->get('girchi_users.utils');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->messenger = $container->get('messenger');
    $instance->state = $container->get('state');

    $user_manager = $instance->entityTypeManager->getStorage('user');
    $user = $user_manager->load($instance->currentUser->id());
    $instance->referral = $user->field_referral->entity;

    $instance->registeredSupportersNumKey = 'u_' . $instance->currentUser->id() . '_registered_supporters_num';
    $instance->registeredSupportersNumber = $instance->state->get($instance->registeredSupportersNumKey);

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
    if ($this->referral) {
      $team = $this->referral->getDisplayName();
    }
    else {
      $team = '--';
    }

    $form['registrator'] = [
      '#markup' => '
            <div class="registrator-box">
                <strong>Registrator: <span class="registrator-name">' . $this->currentUser->getDisplayName() . '</span></strong><br />
                <strong>Team: <span class="team">' . $team . '</span></strong><br />
                <strong>Total registrations: <span class="total-registrations">' . (int) $this->registeredSupportersNumber . '</span></strong><br />
            </div>
       ',
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = $this->entityTypeManager->getStorage('user')->create();
    $values = $form_state->getValues();

    $userName = preg_replace('/@.*$/', '', $values['email']);
    $userName = email_registration_cleanup_username($userName);
    $userName = email_registration_unique_username($userName);

    $user->setPassword('girchi');
    $user->enforceIsNew();
    $user->setEmail($values['email']);
    $user->setUsername($userName);
    $user->set('field_tel', $values['phone']);
    $user->set('field_personal_id', $values['gov_id']);
    $user->set('field_first_name', $values['firstname']);
    $user->set('field_last_name', $values['lastname']);
    $user->set('field_referral', $this->referral->id());
    $user->set('field_ged', $values['ged_amount']);

    $user->activate();

    // Save user account.
    if ($user->save()) {
      $this->messenger->addMessage($values['firstname'] . ' ' . $values['lastname'] . ' is registered successfully!');
      $this->state->set($this->registeredSupportersNumKey, $this->registeredSupportersNumber + 1);
    }
  }

}
