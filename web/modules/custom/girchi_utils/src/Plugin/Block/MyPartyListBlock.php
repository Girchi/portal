<?php

namespace Drupal\girchi_utils\Plugin\Block;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'MyPartyListBlock' block.
 *
 * @Block(
 *  id = "my_party_list_block",
 *  admin_label = @Translation("My party list block"),
 * )
 */
class MyPartyListBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Session\AccountProxyInterface.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $accountProxy;


  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * LoggerFactory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * MyPartyListBlock constructor.
   *
   * @param array $configuration
   *   Array of configuration.
   * @param int $plugin_id
   *   Plugin ID.
   * @param string $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $accountProxy
   *   Current User.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   EntityTypeManager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger.
   */
  public function __construct(array $configuration,
  $plugin_id,
  $plugin_definition,
                              AccountProxyInterface $accountProxy,
                              EntityTypeManager $entityTypeManager,
                              LoggerChannelFactoryInterface $loggerFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->accountProxy = $accountProxy;
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerFactory = $loggerFactory;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    try {
      $members = [];
      $uid = $this->accountProxy->id();
      /** @var \Drupal\user\UserStorage $user_storage */
      $user_storage = $this->entityTypeManager->getStorage('user');
      /** @var \Drupal\user\Entity\User $currentUser */
      $currentUser = $user_storage->load($uid);
      $currentUserGed = $currentUser->get('field_ged')->value ? $currentUser->get('field_ged')->value : 0;
      $membersData = $currentUser->get('field_my_party_list');

      /** @var \Drupal\reference_value_pair\Plugin\Field\FieldType\ReferenceValuePair $member */
      foreach ($membersData as $member) {

        $memberId = $member->get('target_id')->getValue();
        if ($memberId !== NULL) {

          $gedPercentage = $member->get('value')->getValue();
          /** @var \Drupal\user\Entity\User $memberEntity */
          $memberEntity = $user_storage->load($memberId);
          $firstName = $memberEntity->get('field_first_name')->value;
          $lastName = $memberEntity->get('field_last_name')->value;
          $linkToMember = $memberEntity->url();
          $memberGedAmount = $currentUserGed * $gedPercentage / 100;
          /** @var \Drupal\file\Entity\File $avatarEntity */
          $avatarEntity = $memberEntity->get('user_picture')->entity;
          if ($avatarEntity) {
            $memberAvatar = $avatarEntity->getFileUri();
            $isAvatar = TRUE;
          }
          else {
            $memberAvatar = file_create_url(drupal_get_path('theme', 'girchi') . '/images/avatar.png');
            $isAvatar = FALSE;
          }
          $members[$memberId] = [
            'member_first_name' => $firstName,
            'member_last_name' => $lastName,
            'member_ged_percentage' => $gedPercentage,
            'member_profile_picture' => $memberAvatar,
            'member_ged_amount' => $memberGedAmount,
            'link_to_member' => $linkToMember,
            'is_avatar' => $isAvatar,
          ];
        }
      }

    }
    catch (MissingDataException $e) {
      $this->loggerFactory->get('girchi_utils')->error($e->getMessage());
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->get('girchi_utils')->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->get('girchi_utils')->error($e->getMessage());
    }

    return [
      '#theme' => 'my_party_list_block',
      '#members' => $members,
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
