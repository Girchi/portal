<?php

namespace Drupal\girchi_utils\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\KeyValueStore\KeyValueFactory;
use Drupal\girchi_utils\TaxonomyTermTree;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ElectionController.
 *
 * @package Drupal\girchi_utils\Controller
 */
class ElectionController extends ControllerBase {


  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Service to generate taxonomy tree.
   *
   * @var \Drupal\girchi_utils\TaxonomyTermTree
   */
  protected $taxonomyTermTree;

  /**
   * User.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * KeyValueFactory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactory
   */
  protected $keyValue;

  /**
   * ElectionController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\girchi_utils\TaxonomyTermTree $taxonomyTermTree
   *   Taxonomy term tree service.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactory $keyValue
   *   Key value service.
   */
  public function __construct(EntityTypeManager $entityTypeManager, TaxonomyTermTree $taxonomyTermTree, KeyValueFactory $keyValue) {
    $this->entityTypeManager = $entityTypeManager;
    $this->taxonomyTermTree = $taxonomyTermTree;
    $this->keyValue = $keyValue->get('girchi_utils');

    try {
      $userStorage = $this->entityTypeManager->getStorage('user');
      $current_user_id = $this->currentUser()->id();
      $this->user = $userStorage->load($current_user_id);

    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->get('girchi_users')->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->get('girchi_users')->error($e->getMessage());
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('girchi_utils.taxonomy_term_tree'),
      $container->get('keyvalue')
    );
  }

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.Term
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function election() {
    $user_storage = $this->entityTypeManager->getStorage('user');
    $fields = $this->getAdditionalFields();
    $politicians = $user_storage->getQuery()
      ->condition('field_politician', 1)
      ->condition('field_rating_in_party_list', 0, '>')
      ->sort('field_rating_in_party_list', 'ASC')
      ->range(0, 7)
      ->execute();

    $politicians = $user_storage->loadMultiple($politicians);
    $politiciansFullInfo = $this->mergeFieldsWithUsers($politicians, $fields);
    $headerVariables = $this->getCurrentUserInfo();
    $totalAmount = $this->keyValue->get('total_amount');

    $milestones = $this->getMilestons($totalAmount);

    return [
      '#theme' => 'page_election_2020',
      '#type' => 'page',
      '#politicians' => $politiciansFullInfo,
      '#logged_in' => $this->user->isAuthenticated(),
      '#user_header' => $headerVariables,
      '#total_amount' => $totalAmount,
      '#milestones' => $milestones,
    ];
  }

  /**
   * Get additional fields for user from taxonomy term.
   *
   * @return array
   *   Returns array with additionasl fields.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getAdditionalFields() {
    $resultArr = [];
    $taxonomies = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'election_politicians']);
    foreach ($taxonomies as $term) {
      $politician = $term->field_politician->entity->id();
      $description = $term->description->value;
      $url = $term->field_link->value;
      $icon = $term->field_icon->value;
      $iconTitle = $term->field_icon_title->value;
      $resultArr[$politician] = [
        'icon' => $icon,
        'url' => $url,
        'description' => $description,
        'iconTitle' => $iconTitle,
      ];
    }
    return $resultArr;
  }

  /**
   * Merges user fields with taxonomy term fields.
   *
   * @param array $users
   *   Users.
   * @param array $fields
   *   Fileds.`.
   *
   * @return array
   *   Returns merged array.
   */
  protected function mergeFieldsWithUsers(array $users, array $fields) {
    $resultArr = [];
    /** @var \Drupal\user\Entity\User $user */
    foreach ($users as $user) {
      $id = $user->id();
      $firstName = $user->field_first_name->value;
      $lastName = $user->field_last_name->value;
      $politicalGed = $user->field_political_ged->value;
      $ratingInPartyList = $user->field_rating_in_party_list->value;
      $twitterUrl = $user->field_twitter_url->value;
      $facebookUrl = $user->field_facebook_url->value;

      if ($user->get('user_picture')->entity) {
        $img_uri = $user->get('user_picture')->entity->getFileUri();
      }
      else {
        $img_uri = NULL;
      }
      $resultArr[] = [
        'firstName' => $firstName,
        'lastName' => $lastName,
        'politicalGed' => $politicalGed,
        'ratingInPartyList' => $ratingInPartyList,
        'twitterUrl' => $twitterUrl,
        'facebookUrl' => $facebookUrl,
        'imgUri' => $img_uri,
        'icon' => $fields[$id]['icon'] ?? '',
        'url' => $fields[$id]['url'] ?? '',
        'description' => $fields[$id]['description'] ?? '',
        'iconTitle' => $fields[$id]['iconTitle'] ?? '',
      ];
    }
    return $resultArr;
  }

  /**
   * Get current user info.
   *
   * @return array
   *   Returns array with current user info.
   */
  protected function getCurrentUserInfo() {
    $avatarEntity = $this->user->{'user_picture'}->entity;
    if ($avatarEntity) {
      $currentUserAvatar = $avatarEntity->getFileUri();
      $isAvatar = TRUE;
    }
    else {
      $currentUserAvatar = file_create_url(drupal_get_path('theme', 'girchi') . '/images/avatar.png');
      $isAvatar = FALSE;
    }

    return [
      'firstName' => $this->user->field_first_name->value,
      'lastName' => $this->user->field_last_name->value,
      'ged' => $this->user->field_ged->value ? $this->user->field_ged->value : 0,
      'userPicture' => $currentUserAvatar,
      'userName' => $this->user->name->value,
      'isAvatar' => $isAvatar,
      'uid' => $this->user->id(),
    ];
  }

  /**
   * Creates milestones according to total amount.
   *
   * @param int $totalAmount
   *   Total donation amount.
   *
   * @return array
   *   Array of milestones.
   */
  protected function getMilestons($totalAmount) {
    $milestones = [25000, 50000, 75000, 100000, 125000];
    $milestoneStart = 0;
    $milestoneEnd = 25000;
    for ($i = 0; $i < count($milestones); $i++) {
      if ($totalAmount > end($milestones)) {
        $milestoneEnd = $milestoneStart = end($milestones);
        break;
      }
      elseif ($totalAmount > $milestones[$i] && $totalAmount < $milestones[$i + 1]) {
        $milestoneStart = $milestones[$i];
        $milestoneEnd = $milestones[$i + 1];
        break;
      }
    }
    return ['milestoneStart' => $milestoneStart, 'milestoneEnd' => $milestoneEnd];
  }

}
