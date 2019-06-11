<?php

namespace Drupal\girchi_my_party_list\Controller;

use Drupal;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxy;
use Drupal\file\Entity\File;
use Drupal\reference_value_pair\Plugin\Field\FieldType\ReferenceValuePair;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PartyListController.
 */
class PartyListController extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;


  /**
   * Constructs a new PartyListController object.
   * @param EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('entity_type.manager')
    );
  }

  /**
   * Party list.
   *
   * @return array party list
   *   Return My party listh theme string.
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function partyList() {
    $currentUserId = $this->currentUser()->id();
    $currentUser = $this->entityTypeManager->getStorage('user')->load($currentUserId);
    $myPartyList = [];
    $maxPercentage = 100;
    foreach($currentUser->get('field_my_party_list') as $item) {
      $userInfo = $this->getUserInfo($item->getValue()['target_id']);
      $userInfo[0]['percentage'] = $item->getValue()['value'];
      $maxPercentage -= $item->getValue()['value'];
      $myPartyList [] = $userInfo[0];
    }

    $chosenPoliticians = $this->getChosenPoliticians($currentUserId);

    $userStorage = $this->entityTypeManager->getStorage('user');
    $users = $userStorage->getQuery()
      ->condition('field_politician', 1)
      ->range(0,10);
    if(!empty($chosenPoliticians)) {
      $users->condition('uid', $chosenPoliticians, 'NOT IN');
    }
    $users = $users->execute();
    $topPoliticians = $this->getUsersInfo($users);
    return [
      '#type' => 'markup',
      '#theme' => 'girchi_my_party_list',
      '#my_party_list' => $myPartyList,
      '#max_percentage' => $maxPercentage,
      '#top_politicians' => $topPoliticians
    ];
  }

  /**
   * Get Users.
   *
   * @param Request $request
   * @return JsonResponse
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
    public function getUsers(Request $request) {
      $currentUserId = $this->currentUser()->id();
      $politicanUids = $this->getChosenPoliticians($currentUserId);
      $userArray = [];

      $user = $request->get('user');
      $firstName = $lastName = $user;
      $queryOperator = 'CONTAINS';

      if (strpos($user, ' ')) {
        $queryOperator = '=';
        $fulName = explode(' ', $user);
        $firstName = $fulName[0];
        $lastName = $fulName[1];
      }

      try {
        /** @var UserStorage $userStorage */
        $userStorage = $this->entityTypeManager->getStorage('user');
      } catch (InvalidPluginDefinitionException $e) {
        throw $e;
      } catch (PluginNotFoundException $e) {
        throw $e;
      }


      if(!empty($user)) {
        $query = Drupal::entityQuery('user');

        $nameConditions = $query->orConditionGroup()
          ->condition('field_first_name', $firstName, $queryOperator)
          ->condition('field_last_name', $lastName, 'CONTAINS');

        $users = $userStorage->getQuery()
          ->condition($nameConditions)
          ->condition('field_politician', 1, '=')
          ->range(0,10);
        if(!empty($politicanUids)) {
          $users->condition('uid', $politicanUids, 'NOT IN');
        }
        $users = $users->execute();

        $userArray = $this->getUsersInfo($users);
      }
      return new JsonResponse($userArray);
    }

  /**
   * Update current user.
   *
   * @param Request $request
   * @return RedirectResponse response
   * @throws Drupal\Core\Entity\EntityStorageException
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
    public function updateUser(Request $request) {
      $currentUser =  $this->entityTypeManager->getStorage('user')->load($this->currentUser()->id());
      $userList = $request->get('list') ? $request->get('list') : [];

      $userInfo = array_map(function($tag) {
        return [
          'target_id' => $tag['politician'],
          'value' => $tag['percentage'] ? $tag['percentage'] : 0
        ];
      }, $userList);


      $currentUser->get('field_my_party_list')->setValue($userInfo);
      $currentUser->save();
      $redirectUrl = $request->headers->get('referer');
      return new RedirectResponse($redirectUrl);
    }

  /**
   * Get users info.
   *
   * @param array of user ids $users
   * @return array of user info $userArray
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */

    public function  getUsersInfo($users)
    {
      $userArray = [];
      if(!empty($users)) {
        foreach ($users as $user) {
          $user = User::Load($user);
          if($user != null) {
            $firstName = $user->get('field_first_name')->value;
            $lastName = $user->get('field_last_name')->value;
            $imgUrl = '';
            if(!empty($user->get('user_picture')[0])) {
              $imgId = $user->get('user_picture')[0]->getValue()['target_id'];
              $imgFile = File::load($imgId);

              $style = $this->entityTypeManager()->getStorage('image_style')->load('party_member');
              $imgUrl = $style->buildUrl($imgFile->getFileUri());
            }
            $uid = $user->id();
            $userArray[] = [
              "id" => $uid,
              "firstName" => $firstName,
              "lastName" => $lastName,
              "imgUrl" => $imgUrl
            ];
          }
        }
      }
      return $userArray;
    }

  /**
   * Get user info.
   *
   * @param string of user id $user
   * @return array of user info $userArray
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
    public function getUserInfo($userId) {
      if(!empty($userId)) {
        return $this->getUsersInfo([$userId]);
      }
    }


  /**
   * Get user chosen politician by uid
   *
   * @param string of user id $userId
   * @return array politician uids
   * @throws InvalidP`luginDefinitionException
   * @throws PluginNotFoundException
   */
    public function getChosenPoliticians($userId){
      $politicianUids = [];
      $currentUser = $this->entityTypeManager->getStorage('user')->load($userId);
      $chosenPoliticians = $currentUser->get('field_my_party_list')->referencedEntities();
      foreach ($chosenPoliticians as $politician) {
        $politicianUids[] = $politician->id();
      }
      return $politicianUids;
    }
}

