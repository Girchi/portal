<?php

namespace Drupal\girchi_referral\Plugin\Block;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'TopReferrals' block.
 *
 * @Block(
 *  id = "top_referrals",
 *  admin_label = @Translation("Top referrals"),
 * )
 */
class TopReferrals extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactory $loggerFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    try {
      $user_storage = $this->entityTypeManager->getStorage('user');
      $uids = $user_storage->getQuery()
        ->condition('field_referral_benefits', 0, '>')
        ->sort('field_referral_benefits', "DESC")
        ->execute();

      $users = $user_storage->loadMultiple($uids);
      $top_referrals = [];
      foreach ($users as $user) {
        /** @var \Drupal\user\Entity\User $user */
        $uid = $user->id();
        $user_name = $user->get('field_first_name')->value ?? '';
        $user_surname = $user->get('field_last_name')->value ?? '';
        $referral_benefits = $user->get('field_referral_benefits')->value;
        if ($user->get('user_picture')->entity) {
          $profilePictureEntity = $user->get('user_picture')->entity;
          $profilePicture = $profilePictureEntity->getFileUri();
        }
        else {
          $profilePicture = NULL;
        }

        // Get user referral for modal.
        $referral_id = $user_storage->getQuery()
          ->condition('field_referral', $uid, '=')
          ->execute();
        $referral_count = $user_storage->getQuery()
          ->condition('field_referral', $uid, '=')
          ->count()
          ->execute();
        $referrals = $user_storage->loadMultiple($referral_id);
        $refs = [];
        foreach ($referrals as $referral) {
          $referral_id = $referral->id();
          $referral_name = $referral->get('field_first_name')->value ?? '';
          $referral_surname = $referral->get('field_last_name')->value ?? '';
          if ($referral->get('user_picture')->entity) {
            $referralPictureEn = $referral->get('user_picture')->entity;
            $referralProfilePicture = $referralPictureEn->getFileUri();
          }
          else {
            $referralProfilePicture = NULL;
          }
          $refs[] = [
            'referral_id' => $referral_id,
            'referral_name' => $referral_name,
            'referral_surname' => $referral_surname,
            'referral_img' => $referralProfilePicture,
          ];

        }

        $top_referrals[] = [
          'uid' => $uid,
          'user_name' => $user_name,
          'user_surname' => $user_surname,
          'referral_benefits' => $referral_benefits,
          'img' => $profilePicture,
          'referrals' => $refs,
          'referral_count' => $referral_count,

        ];

      }
      return [
        '#theme' => 'top_referrals',
        '#topReferrals' => $top_referrals,
      ];

    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->error($e->getMessage());

    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->error($e->getMessage());
    }

    return [
      '#theme' => 'top_referrals',
      '#topReferrals' => [],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['node_list']);
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return \Drupal\girchi_referral\Plugin\Block\TopReferrals
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('logger.factory')
    );
  }

}
