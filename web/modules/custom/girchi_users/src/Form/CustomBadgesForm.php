<?php

namespace Drupal\girchi_users\Form;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\girchi_notifications\Constants\NotificationConstants;
use Drupal\girchi_notifications\GetBadgeInfo;
use Drupal\girchi_notifications\NotifyUserService;
use Drupal\girchi_users\CustomBadgesService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CustomBadgesForm.
 */
class CustomBadgesForm extends FormBase {

  /**
   * Badges.
   *
   * @var \Drupal\girchi_users\CustomBadgesService
   */
  public $badges;

  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  public $entityTypeManager;

  /**
   * Json.
   *
   * @var \Drupal\Component\Serialization\Json
   */
  public $json;

  /**
   * LoggerFactory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  public $loggerFactory;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * NotifyUserService.
   *
   * @var \Drupal\girchi_notifications\NotifyUserService
   */
  protected $notifyUser;

  /**
   * GetBadgeInfo.
   *
   * @var \Drupal\girchi_notifications\GetBadgeInfo
   */
  protected $getBadgeInfoService;

  /**
   * Constructs a new CustomBadgesForm object.
   *
   * @param \Drupal\girchi_users\CustomBadgesService $customBadgesService
   *   CustomBadgesService.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   EntityTypeManager.
   * @param \Drupal\Component\Serialization\Json $json
   *   Json.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   LoggerFactory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger.
   * @param \Drupal\girchi_notifications\NotifyUserService $notifyUserService
   *   NotifyUserService.
   * @param \Drupal\girchi_notifications\GetBadgeInfo $getBadgeInfo
   *   GetBadgeInfo.
   */
  public function __construct(CustomBadgesService $customBadgesService,
                              EntityTypeManagerInterface $entityTypeManager,
                              Json $json,
                              LoggerChannelFactory $loggerFactory,
                              MessengerInterface $messenger,
                              NotifyUserService $notifyUserService,
                              GetBadgeInfo $getBadgeInfo) {
    $this->badges = $customBadgesService->getCustomBadges(FALSE);
    $this->entityTypeManager = $entityTypeManager;
    $this->json = $json;
    $this->loggerFactory = $loggerFactory->get('girchi_users');
    $this->messenger = $messenger;
    $this->notifyUser = $notifyUserService;
    $this->getBadgeInfoService = $getBadgeInfo;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('girchi_users.custom_badges'),
      $container->get('entity_type.manager'),
      $container->get('serialization.json'),
      $container->get('logger.factory'),
      $container->get('messenger'),
      $container->get('girchi_notifications.notify_user'),
      $container->get('girchi_notifications.get_badge_info')
    );
  }

  /**
   * {@inheritdoc}reqreuest.
   */
  public function getFormId() {
    return 'custom_badges_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['user'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      "#bundle" => "user",
      '#attributes' => [
        'placeholder' => $this->t('Enter user name'),
      ],
    ];

    $form['badge'] = [
      '#type' => 'select',
      '#options' => $this->badges,
      '#required' => FALSE,
      '#empty_value' => '',
      '#empty_option' => $this->t('- Select badge -'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#attributes' => [
        'class' => [
          'btn btn-lg btn-block btn-warning text-uppercase mt-4',
        ],
      ],
      '#value' => $this->t('Apply badge'),
    ];

    $form['#cache'] = ['max-age' => 0];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user_id = $form_state->getValue('user');
    $badge_id = $form_state->getValue('badge');

    if (!empty($user_id) && !empty($badge_id)) {
      /** @var \Drupal\user\Entity\User $user_storage */
      try {
        $user_storage = $this->entityTypeManager->getStorage('user');
        $user = $user_storage->load($user_id);
        /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $user_badges */
        $user_badges = $user->get('field_badges');
        $appearance_array = [
          'visibility' => TRUE,
          'selected' => FALSE,
          'approved' => TRUE,
          'status_message' => '',
          'earned_badge' => TRUE,
        ];
        $encoded_Value = $this->json->encode($appearance_array);

        // Set parameters for NotifyUserService.
        $badge_info = $this->getBadgeInfoService->getBadgeInfo($badge_id);
        $text = "თქვენ მოგენიჭათ ბეჯი - ${badge_info['badge_name']}.";
        $text_en = "You have acquired the badge - ${badge_info['badge_name_en']}.";
        $notification_type = NotificationConstants::USER_BADGE;
        $notification_type_en = NotificationConstants::USER_BADGE_EN;

        if (empty($user_badges->value)) {
          $user_badges->appendItem([
            'target_id' => $badge_id,
            'value' => $encoded_Value,
          ]);
          $user->save();
          $this->messenger->addMessage($this->t('User has successfully acquired the badge!'));
          // Notify user.
          $this->notifyUser->notifyUser($user_id, $badge_info, $notification_type, $notification_type_en, $text, $text_en);
        }
        else {
          foreach ($user_badges as $user_badge) {
            if ($user_badge->target_id == $badge_id) {
              $current_value = $user_badge->value;
              $decoded_value = $this->json->decode($current_value);
              if ($decoded_value['approved'] == TRUE) {
                $this->messenger->addError($this->t('User already has this badge!'));
                $form_state->setRebuild();
              }
              else {
                $user_badge->set('value', $encoded_Value);
                $user->save();
                $this->messenger->addMessage($this->t('User has successfully acquired the badge!'));
                // Notify user.
                $this->notifyUser->notifyUser($user_id, $badge_info, $notification_type, $notification_type_en, $text, $text_en);
              }

            }
          }
        }

      }
      catch (InvalidPluginDefinitionException $e) {
        $this->loggerFactory->error($e->getMessage());
      }
      catch (PluginNotFoundException $e) {
        $this->loggerFactory->error($e->getMessage());
      }
      catch (EntityStorageException $e) {
        $this->loggerFactory->error($e->getMessage());
      }
    }
    else {
      $this->messenger->addError($this->t('Error'));
      $form_state->setRebuild();
    }

  }

}
