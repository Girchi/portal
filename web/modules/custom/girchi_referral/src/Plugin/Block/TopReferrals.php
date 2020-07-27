<?php

namespace Drupal\girchi_referral\Plugin\Block;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\user\Entity\User;
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
  private const REFERRALS_ALL = 0;
  private const REFERRALS_MONTHLY = 1;
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

  /**
   * {@inheritdoc}
   */
  public function build() {
    $all_referrals = $this->getReferrals();
    $monthly_referrals = $this->getReferrals(self::REFERRALS_MONTHLY);

    return [
      '#theme' => 'top_referrals',
      '#topReferrals' => $all_referrals,
      '#topReferralsMonthly' => $monthly_referrals,
    ];

  }

  /**
   * GetReferrals.
   *
   * @param int $mode
   *   Mode.
   *
   * @return array|array[]
   *   Top referrals.
   */
  public function getReferrals($mode = self::REFERRALS_ALL) {
    try {
      $user_storage = $this->entityTypeManager->getStorage('user');
      $top_referrals = [];

      if ($mode == self::REFERRALS_ALL) {
        $uids = $user_storage->getQuery()
          ->condition('field_referral_benefits', 0, '>')
          ->condition('field_first_name', NULL, 'IS NOT NULL')
          ->condition('field_last_name', NULL, 'IS NOT NULL')
          ->sort('field_referral_benefits', "DESC")
          ->execute();

        $users = $user_storage->loadMultiple($uids);
        foreach ($users as $user) {
          if ($user) {
            $user_info = $this->getUserInfo($user);
            $uid = $user->id();
            $user_info['referral_benefits'] = $user->get('field_referral_benefits')->value;
            $top_referrals[$uid] = $user_info;
          }
        }
      }

      elseif ($mode == self::REFERRALS_MONTHLY) {
        $start_month = strtotime("first day of this month");
        $end_month = strtotime("last day of this month");

        $referral_benefits_storage = $this->entityTypeManager->getStorage('node');
        $referral_benefit_ids = $referral_benefits_storage->getQuery()
          ->condition('type', 'referral_transaction')
          ->condition('created', $start_month, '>=')
          ->condition('created', $end_month, '<=')
          ->execute();

        $referral_benefits_recs = $referral_benefits_storage->loadMultiple($referral_benefit_ids);
        foreach ($referral_benefits_recs as $referral_benefits_rec) {
          $uid = $referral_benefits_rec->get('field_referral')->target_id;
          /** @var \Drupal\user\Entity\User $user */
          $user = $user_storage->load($uid);
          if ($user) {
            // dump($user);
            $amount_of_money = $referral_benefits_rec->get('field_amount_of_money')->value;
            $user_info = $this->getUserInfo($user);
            $user_info['referral_benefits'] = $amount_of_money;
            if (array_key_exists($uid, $top_referrals)) {
              $top_referrals[$uid]['referral_benefits'] += $amount_of_money;
            }
            else {
              $top_referrals[$uid] = $user_info;
            }
          }
        }
      }
      usort($top_referrals, function ($a, $b) {
        return $b['referral_benefits'] - $a['referral_benefits'];
      });
      return $top_referrals;

    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->error($e->getMessage());

    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->error($e->getMessage());
    }

    return [
      '#topReferrals' => [],
    ];

  }

  /**
   * Get user info.
   *
   * @param \Drupal\user\Entity\User $user
   *   User.
   *
   * @return array|array[]
   *   Top referrals.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getUserInfo(User $user) {
    $user_storage = $this->entityTypeManager->getStorage('user');
    /** @var \Drupal\user\Entity\User $user */
    $uid = $user->id();
    $user_name = $user->get('field_first_name')->value ?? '';
    $user_surname = $user->get('field_last_name')->value ?? '';
    // $referral_benefits = $user->get('field_referral_benefits')->value;
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
      ->condition('field_first_name', NULL, 'IS NOT NULL')
      ->condition('field_last_name', NULL, 'IS NOT NULL')
      ->execute();
    $referral_count = count($referral_id);
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

    $top_referrals = [
      'uid' => $uid,
      'user_name' => $user_name,
      'user_surname' => $user_surname,
    // 'referral_benefits' => $referral_benefits,
      'img' => $profilePicture,
      'referrals' => $refs,
      'referral_count' => $referral_count,

    ];

    return $top_referrals;

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['node_list']);
  }

}
