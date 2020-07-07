<?php

namespace Drupal\girchi_my_party_list;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class PartyListCalculatorService.
 */
class PartyListCalculatorService {

  /**
   * EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Request Stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;
  /**
   * Database.
   *
   * @var \Drupal\Core\Database\Database
   */
  private $database;
  /**
   * LoggerFactory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  private $loggerFactory;

  /**
   * Constructs a new PartyListCalculatorService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *
   *   Logger factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *
   *   Request stack;.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   LoggerFactory.
   * @param \Drupal\Core\Database\Connection $database
   *   Database.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              RequestStack $requestStack,
                              LoggerChannelFactory $loggerFactory,
                              Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $requestStack->getCurrentRequest();
    $this->loggerFactory = $loggerFactory->get('girchi_party_list');
    $this->database = $database;
  }

  /**
   * Calculate.
   */
  public function calculate() {
    try {
      // Array for full party list.
      $user_rating = [];

      /**
       * @var \Drupal\user\Entity\UserStorage $users
       */
      $user_storage = $this->entityTypeManager
        ->getStorage('user');
      $query = $this->database->select('user__field_my_party_list', 'pr');
      $query->leftJoin('user__field_ged', 'ged', 'pr.entity_id = ged.entity_id');
      $query->leftJoin('user__field_first_name', 'fn', 'pr.entity_id = fn.entity_id');
      $query->leftJoin('user__field_last_name', 'ln', 'pr.entity_id = ln.entity_id');
      $query->leftJoin('user__field_publicity', 'pb', 'pr.entity_id = pb.entity_id');
      $query
        ->fields('pr', ['entity_id',
          'field_my_party_list_target_id',
          'field_my_party_list_value',
        ])
        ->fields('ged', ['field_ged_value'])
        ->fields('pb', ['field_publicity_value'])
        ->condition('pr.field_my_party_list_target_id', NULL, 'IS NOT NULL')
        ->condition('pr.field_my_party_list_value', NULL, 'IS NOT NULL')
        ->condition('fn.field_first_name_value', NULL, 'IS NOT NULL')
        ->condition('ln.field_last_name_value', NULL, 'IS NOT NULL')
        ->condition('pb.field_publicity_value', '1', '=');
      $results = $query->execute()->fetchAll();
      foreach ($results as $result) {
        $id = $result->field_my_party_list_target_id;
        $percent = $result->field_my_party_list_value;
        $ged = $result->field_ged_value;
        if (isset($user_rating[$id])) {
          $user_rating[$id] += $ged * ($percent / 100);
        }
        else {
          $user_rating[$id] = $ged * ($percent / 100);
        }
      }
      arsort($user_rating);

      $rating_number = 1;
      $politicians = $user_storage->loadMultiple(array_keys($user_rating));

      foreach ($politicians as $politician) {
        try {
          /**
           * @var \Drupal\user\Entity\User $politician
           */
          $politician->set('field_political_ged', $user_rating[$politician->id()]);
          $politician->set('field_rating_in_party_list', $rating_number);
          $politician->save();
          $rating_number++;
        }
        catch (\Exception $e) {
          $this->loggerFactory->error($e->getMessage());
        }
      }
      $unlistedPoliticians = $user_storage->getQuery()
        ->condition('field_politician', TRUE, '=')
        ->condition('uid', array_keys($user_rating), 'NOT IN')
        ->execute();
      $unlistedPoliticians = $user_storage->loadMultiple($unlistedPoliticians);
      foreach ($unlistedPoliticians as $politician) {
        $politician->set('field_political_ged', NULL);
        $politician->set('field_rating_in_party_list', NULL);
        $politician->save();
      }
    }
    catch (\Exception $e) {
      $this->loggerFactory->error($e->getMessage());
    }
  }

  /**
   * Get users who support politicians.
   *
   * @param array $politician_ids
   *   Politicians Ids.
   *
   * @return array
   *   Returns all politicians supporters.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getPoliticiansSupporters(array $politician_ids) {
    $base_url = $this->requestStack->getSchemeAndHttpHost();
    $returnArray = [];
    $storage = $this->entityTypeManager->getStorage('user');

    $users = $storage
      ->getQuery()
      ->condition('field_ged', '0', '>')
      ->condition('field_my_party_list', $politician_ids, 'IN')
      ->condition('field_first_name', NULL, 'IS NOT NULL')
      ->condition('field_last_name', NULL, 'IS NOT NULL')
      ->condition('field_publicity', '1', '=')
      ->execute();

    /** @var User $users */
    $users = $storage->loadMultiple($users);

    /** @var EntityReferenceFieldItemListAlias $field */
    foreach ($users as $user) {
      if (!empty($user->get('user_picture')[0])) {
        $img_id = $user->get('user_picture')[0]->getValue()['target_id'];
        $img_file = $this->entityTypeManager->getStorage('file')->load($img_id);
        $style = ImageStyle::load('party_member');
        $img_url = $style->buildUrl($img_file->getFileUri());
      }
      else {
        $img_url = $base_url . '/themes/custom/girchi/images/avatar34x34.png';
      }
      $first_name = $user->get('field_first_name')->value;
      $last_name = $user->get('field_last_name')->value;
      $user_info = [
        'img_url' => $img_url,
        'name' => implode(" ", [$first_name, $last_name]),
        'id' => $user->id(),
      ];
      // Calculate final ged amount.
      $ged_amount = $user->get('field_ged')->value;
      $party_list = $user->get('field_my_party_list');
      foreach ($party_list as $supporter) {
        if (in_array($supporter->target_id, $politician_ids)) {
          $supported_ged = ["ged_amount" => $ged_amount * ($supporter->value / 100)];
          $percentage = ["percentage" => $supporter->value];
          $returnArray[$supporter->target_id][] = array_merge($user_info, $supported_ged, $percentage);
        }
      }
    }

    foreach ($returnArray as $key => $politician) {
      usort($returnArray[$key], function ($a, $b) {
        return $a['ged_amount'] > $b['ged_amount'] ? -1 : 1;
      });
    }
    return $returnArray;
  }

}
