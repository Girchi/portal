<?php

namespace Drupal\girchi_utils\Plugin\Block;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\girchi_users\GEDHelperService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'MyPartyListBlock' block.
 *
 * @Block(
 *  id = "my_party_list_block",
 *  admin_label = @Translation("My party list block"),
 * )
 */
class MyPartyListBlock extends BlockBase implements ContainerFactoryPluginInterface
{

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
   * LoggerFactory.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $pathCurrent;

  /**
   * Ged Helper service.
   *
   * @var \Drupal\girchi_users\GEDHelperService
   */
  protected $gedHelper;

  /**
   * Current route matcher.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $router;

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
   * @param \Drupal\Core\Path\CurrentPathStack $pathCurrent
   *   PathCurrent.
   * @param \Drupal\girchi_users\GEDHelperService $ged_helper
   *   GeD helper service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $router
   *   Current route matcher.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountProxyInterface $accountProxy,
    EntityTypeManager $entityTypeManager,
    LoggerChannelFactoryInterface $loggerFactory,
    CurrentPathStack $pathCurrent,
    GEDHelperService $ged_helper,
    RouteMatchInterface $router
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->accountProxy = $accountProxy;
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerFactory = $loggerFactory;
    $this->pathCurrent = $pathCurrent;
    $this->gedHelper = $ged_helper;
    $this->router = $router;
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
      $container->get('logger.factory'),
      $container->get('path.current'),
      $container->get('girchi_users.ged_helper'),
      $container->get('current_route_match')

    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $gedHelper = $this->gedHelper;

    try {
      $members = [];
      /** @var \Drupal\user\UserStorage $user_storage */
      $user_storage = $this->entityTypeManager->getStorage('user');
      /** @var \Drupal\user\Entity\User $currentUser */
      $currentUser = $this->router
        ->getParameter('user');

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
            'uid' => $memberId,
            'member_first_name' => $firstName,
            'member_last_name' => $lastName,
            'member_ged_percentage' => $gedPercentage,
            'member_profile_picture' => $memberAvatar,
            'member_ged_amount' => $gedHelper::getFormattedGED($memberGedAmount),
            'member_ged_amount_long' => $memberGedAmount,
            'link_to_member' => $linkToMember,
            'is_avatar' => $isAvatar,
          ];
        }
      }
      usort($members, function ($a, $b) {
        if ($a['member_ged_percentage'] == $b['member_ged_percentage']) {
          return 0;
        }

        return $a['member_ged_percentage'] < $b['member_ged_percentage'] ? 1 : -1;
      });

      // Load top 5 users.
      $members_short = array_slice($members, 0, 5, TRUE);
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
      '#members_short' => $members_short,
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
