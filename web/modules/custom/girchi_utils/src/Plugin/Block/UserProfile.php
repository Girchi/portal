<?php

namespace Drupal\girchi_utils\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\user\Entity\User;
/**
 * Provides a 'UserProfile' block.
 *
 * @Block(
 *  id = "user_profile",
 *  admin_label = @Translation("User profile"),
 * )
 */
class UserProfile extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state)
  {

    $form['user_profile_ged'] = [
        '#type' => 'checkbox',
        '#title' => 'Show Ged',
        '#default_value' => isset($this->configuration['ged']) ? $this->configuration['ged'] : 1  ,
    ];
    $form['user_profile_member'] = [
        '#type' => 'checkbox',
        '#title' => 'Show Member',
        '#default_value' => isset($this->configuration['member']) ? $this->configuration['member'] : 1
    ];
    return $form;
  }

  public function blockSubmit($form, FormStateInterface $form_state) {

    $this->configuration['ged'] = $form_state->getValue('user_profile_ged');
    $this->configuration['member'] = $form_state->getValue('user_profile_member');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $currentUserId = \Drupal::currentUser()->id();
    $currentUser = User::load($currentUserId);
    $currentUserFirstName = $currentUser->get('field_first_name')->value;
    $currentUserLastName = $currentUser->get('field_last_name')->value;
    $currentUserGed = $currentUser->get('field_ged')->value ?  $currentUser->get('field_ged')->value : 0 ;
      /** @var File $avatarEntity */
    $avatarEntity = $currentUser->get('user_picture')->entity;
    $currentRank = $currentUser->get('field_rank')->value;
    $numberOfUsers = \Drupal::entityQuery('user')
          ->sort('created', 'DESC')
          ->count()
          ->execute();

    if($avatarEntity) {
      $currentUserAvatar = $avatarEntity->getFileUri();
      $isAvatar = true;
    }else{
      $currentUserAvatar = file_create_url( drupal_get_path('theme', 'girchi') . '/images/avatar.png');
      $isAvatar = false;
    }

    $build = [];
    $build['user_profile']['#markup'] = 'Implement UserProfile.';

    return array(
      '#theme' => 'user_profile',
      '#title' => t('User Profile'),
      '#description' => 'User profile block',
      '#ged' => $this->configuration['ged'],
      '#member'=> $this->configuration['member'],
      '#user_id' => $currentUserId,
      '#user_first_name' => $currentUserFirstName,
      '#user_last_name' => $currentUserLastName,
      '#user_ged' => $currentUserGed,
      '#user_profile_picture' => $currentUserAvatar,
      '#user_count' => $numberOfUsers-1,
      '#user_rank' => $currentRank,
      '#is_avatar'=> $isAvatar
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['user']);
  }

}
