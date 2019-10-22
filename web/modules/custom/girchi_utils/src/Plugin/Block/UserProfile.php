<?php

namespace Drupal\girchi_utils\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'UserProfile' block.
 *
 * @Block(
 *  id = "user_profile",
 *  admin_label = @Translation("User profile"),
 * )
 */
class UserProfile extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Session\AccountProxyInterface.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $accountProxy;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $accountProxy) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->accountProxy = $accountProxy;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $form['user_profile_ged'] = [
      '#type' => 'checkbox',
      '#title' => 'Show Ged',
      '#default_value' => isset($this->configuration['ged']) ? $this->configuration['ged'] : 1,
    ];
    $form['user_profile_member'] = [
      '#type' => 'checkbox',
      '#title' => 'Show Member',
      '#default_value' => isset($this->configuration['member']) ? $this->configuration['member'] : 1,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {

    $this->configuration['ged'] = $form_state->getValue('user_profile_ged');
    $this->configuration['member'] = $form_state->getValue('user_profile_member');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $user_storage = $this->entityTypeManager->getStorage('user');
    $currentUserId = $this->accountProxy->getAccount()->id();
    $currentUser = $user_storage->load($currentUserId);
    $currentUserFirstName = $currentUser->get('field_first_name')->value;
    $currentUserLastName = $currentUser->get('field_last_name')->value;
    $currentUserGed = $currentUser->get('field_ged')->value ? $currentUser->get('field_ged')->value : 0;
    /** @var \Drupal\file\Entity\File $avatarEntity */
    $avatarEntity = $currentUser->get('user_picture')->entity;
    $currentRank = $currentUser->get('field_rank')->value;
    $numberOfUsers = $user_storage
      ->getQuery()
      ->sort('created', 'DESC')
      ->count()
      ->execute();

    if ($avatarEntity) {
      $currentUserAvatar = $avatarEntity->getFileUri();
      $isAvatar = TRUE;
    }
    else {
      $currentUserAvatar = file_create_url(drupal_get_path('theme', 'girchi') . '/images/avatar.png');
      $isAvatar = FALSE;
    }

    return [
      '#theme' => 'user_profile',
      '#title' => $this->t('User Profile'),
      '#description' => $this->t('User profile block'),
      '#ged' => $this->configuration['ged'],
      '#member' => $this->configuration['member'],
      '#user_id' => $currentUserId,
      '#user_first_name' => $currentUserFirstName,
      '#user_last_name' => $currentUserLastName,
      '#user_ged' => $currentUserGed,
      '#user_profile_picture' => $currentUserAvatar,
      '#user_count' => $numberOfUsers - 1,
      '#user_rank' => $currentRank,
      '#is_avatar' => $isAvatar,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['user']);
  }

}
