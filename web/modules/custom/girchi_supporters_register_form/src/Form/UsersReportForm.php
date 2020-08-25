<?php

namespace Drupal\girchi_supporters_register_form\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class UsersReportForm.
 */
class UsersReportForm extends FormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Messenger\MessengerInterface definition.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Drupal\Core\State\StateInterface definition.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Symfony\Component\HttpFoundation\RequestStack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->messenger = $container->get('messenger');
    $instance->state = $container->get('state');
    $instance->requestStack = $container->get('request_stack');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'users_report_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['container', 'form--inline']],
    ];

    $request = $this->requestStack->getCurrentRequest();

    $start_date = $request->query->get('start_date');
    $end_date = $request->query->get('end_date');

    if (!$start_date || !$end_date) {
      $start_date_object = new DrupalDateTime('-1 week');
      $end_date_object = new DrupalDateTime('now');
    }
    else {
      $start_date_object = new DrupalDateTime($start_date);
      $end_date_object = new DrupalDateTime($end_date);
    }

    $form['wrapper']['start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Start Date'),
      '#weight' => '0',
      '#default_value' => date('Y-m-d', $start_date_object->getTimestamp()),
    ];

    $form['wrapper']['end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('End Date'),
      '#weight' => '0',
      '#default_value' => date('Y-m-d', $end_date_object->getTimestamp()),
    ];

    $form['wrapper']['submit_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form-actions']],
    ];

    $form['wrapper']['submit_wrapper']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];

    $form['wrapper']['table'] = [
      '#type' => 'table',
      '#caption' => $this->t('Registrators'),
      '#sticky' => TRUE,
      '#header' => [
        $this->t('#'),
        $this->t('Name'),
        $this->t('New members'),
        $this->t('New supporters'),
        ['data' => $this->t('Accumulated GED'), 'sort' => 'desc'],
      ],
    ];

    $report = $this->getReportData();
    $i = 0;
    foreach ($report as $obj) {
      $i++;
      $form['wrapper']['table']['#rows'][$i]['#'] = $i;
      $form['wrapper']['table']['#rows'][$i]['name'] = $obj['registrator']->getUsername();
      $form['wrapper']['table']['#rows'][$i]['new_members'] = $obj['member']['count'];
      $form['wrapper']['table']['#rows'][$i]['new_supporters'] = $obj['supporter']['count'];
      $form['wrapper']['table']['#rows'][$i]['geds'] = $obj['member']['count'] * 1000 + $obj['supporter']['count'] * 500;
    }

    return $form;
  }

  /**
   * Generate report data.
   *
   * @return array
   *   Report array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getReportData() {
    $request = $this->requestStack->getCurrentRequest();

    $start_date = $request->query->get('start_date');
    $end_date = $request->query->get('end_date');

    if (!$start_date || !$end_date) {
      return [];
    }

    $usersStorage = $this->entityTypeManager->getStorage('user');
    $misioner_ids = $usersStorage->getQuery()
      ->condition('status', '1')
      ->condition('roles', 'missioner')
      ->execute();

    $user_ids = $usersStorage->getQuery()
      ->condition('created', strtotime($start_date), '>=')
      ->condition('created', strtotime($end_date), '<=')
      ->condition('field_referral', $misioner_ids, 'IN')
      ->condition('roles', ['registered_supporter', 'registered_member'], 'IN')
      ->execute();

    if ($misioners = $usersStorage->loadMultiple($misioner_ids)) {
      if ($users = $usersStorage->loadMultiple($user_ids)) {
        $report_group_by_referrals = [];
        foreach ($users as $user) {
          $referral_id = $user->get('field_referral')->getValue()[0]['target_id'];
          $report_group_by_referrals[$referral_id]['registrator'] = $misioners[$referral_id];

          if (in_array('registered_member', $user->getRoles())) {
            $type = 'member';
          }
          elseif (in_array('registered_supporter', $user->getRoles())) {
            $type = 'supporter';
          }

          if (isset($report_group_by_referrals[$referral_id][$type]['count'])) {
            $report_group_by_referrals[$referral_id][$type]['count']++;
          }
          else {
            $report_group_by_referrals[$referral_id][$type]['count'] = 1;
          }
        }
        usort($report_group_by_referrals, function ($a, $b) {
          return ($a['count'] < $b['count']);
        });
        return $report_group_by_referrals;
      }
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('girchi_supporters_register_form.users_report_form',
      [],
      [
        'query' => [
          'start_date' => $form_state->getValue('start_date'),
          'end_date' => $form_state->getValue('end_date'),
        ],
      ]
    );
  }

}
