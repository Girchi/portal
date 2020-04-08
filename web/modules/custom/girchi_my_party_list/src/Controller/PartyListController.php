<?php

namespace Drupal\girchi_my_party_list\Controller;

use Drupal\Core\Render\Renderer;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\girchi_my_party_list\PartyListCalculatorService;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Render\Renderer definition.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Drupal\girchi_my_party_list\PartyListCalculatorService.
   *
   * @var \Drupal\girchi_my_party_list\PartyListCalculatorService
   *
   *   Party list calculator.
   */
  protected $partyListCalculator;

  /**
   * Constructs a new PartyListController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *
   *   Entity type manager.
   * @param \Drupal\Core\Render\Renderer $renderer
   *
   *   Renderer.
   * @param \Drupal\girchi_my_party_list\PartyListCalculatorService $partyListCalculator
   *
   *   Party list calculator.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Renderer $renderer, PartyListCalculatorService $partyListCalculator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->partyListCalculator = $partyListCalculator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('girchi_my_party_list.party_list_calculator')
    );
  }

  /**
   * Party list.
   *
   * @return array
   *   Return My party listh theme string.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function partyList() {
    $currentUserId = $this->currentUser()->id();
    $currentUser = $this->entityTypeManager->getStorage('user')->load($currentUserId);
    $myPartyList = [];
    $maxPercentage = 100;
    foreach ($currentUser->get('field_my_party_list') as $item) {
      $userInfo = $this->getUserInfo($item->getValue()['target_id']);
      $userInfo[0]['percentage'] = $item->getValue()['value'];
      $maxPercentage -= $item->getValue()['value'];
      $myPartyList[] = $userInfo[0];
    }

    $userStorage = $this->entityTypeManager->getStorage('user');
    $users = $userStorage->getQuery()
      ->condition('field_politician', 1)
      ->condition('field_first_name', NULL, 'IS NOT NULL')
      ->condition('field_last_name', NULL, 'IS NOT NULL');

    $users = $users->execute();
    $topPoliticians = $this->getUsersInfo($users);
    return [
      '#type' => 'markup',
      '#theme' => 'girchi_my_party_list',
      '#my_party_list' => $myPartyList,
      '#max_percentage' => $maxPercentage,
      '#top_politicians' => $topPoliticians,
    ];
  }

  /**
   * Update current user.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   *   Request.
   *
   * @return mixed
   *
   *   Json Response
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function updateUser(Request $request) {
    $currentUser = $this->entityTypeManager->getStorage('user')->load($this->currentUser()->id());
    $userList = $request->get('list') ? $request->get('list') : [];
    $redirectUrl = Url::fromRoute('girchi_my_party_list.party_list_controller_partyList')->toString();

    $max_value = 100;
    foreach ($userList as $userListItem) {
      $percentage = (int) $userListItem['percentage'];
      if ($percentage > 100 || $percentage < 0) {
        $redirectUrl .= '?error=percentage';
        return new RedirectResponse($redirectUrl);
      }

      if ($percentage > $max_value) {
        $redirectUrl .= '?error=percentage';
        return new RedirectResponse($redirectUrl);
      }
      else {
        $max_value -= $percentage;
      }
    }
    $userInfo = array_map(function ($tag) {
      return [
        'target_id' => $tag['politician'],
        'value' => $tag['percentage'] ? (int) $tag['percentage'] : 0,
      ];
    }, $userList);
    $currentUser->get('field_my_party_list')->setValue($userInfo);
    $currentUser->save();

    // If user has not checked publicity checkbox add this message.
    if ($currentUser->get('field_publicity')->value != 1) {
      $this->messenger()->addWarning($this->t('If you want your activities to appear in party list, you need to check "I agree on publicity" on your profile.'));
    }
    return new RedirectResponse($redirectUrl);
  }

  /**
   * Get users info.
   *
   * @param array $users
   *
   *   Users.
   *
   * @return array
   *
   *   Array of users
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getUsersInfo(array $users) {
    $userArray = [];
    if (!empty($users)) {
      foreach ($users as $user) {
        $user = User::Load($user);
        if ($user != NULL) {
          $firstName = $user->get('field_first_name')->value;
          $lastName = $user->get('field_last_name')->value;
          $imgUrl = '';
          if (!empty($user->get('user_picture')[0])) {
            $imgId = $user->get('user_picture')[0]->getValue()['target_id'];
            $imgFile = $this->entityTypeManager->getStorage('file')->load($imgId);
            $style = $this->entityTypeManager->getStorage('image_style')->load('party_member');
            $imgUrl = $style->buildUrl($imgFile->getFileUri());
          }
          else {
            $imgUrl = file_create_url(drupal_get_path('theme', 'girchi') . '/images/avatar.png');
          }
          $uid = $user->id();
          $userArray[] = [
            "id" => $uid,
            "firstName" => $firstName,
            "lastName" => $lastName,
            "imgUrl" => $imgUrl,
          ];
        }
      }
    }
    return $userArray;
  }

  /**
   * Get user info.
   *
   * @param string $userId
   *
   *   UserId.
   *
   * @return array
   *
   *   User Info
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getUserInfo($userId) {
    if (!empty($userId)) {
      return $this->getUsersInfo([$userId]);
    }
  }

  /**
   * Get Users.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   *   Request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *
   *   Response
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getPoliticianSupporters(Request $request) {

    $userId = $request->request->get('userId');

    $supporters = $this->partyListCalculator->getPoliticiansSupporters([$userId]);
    $build = [
      '#type' => 'markup',
      '#theme' => 'girchi_party_list',
      '#supporters' => $supporters,
    ];
    $html = $this->renderer->renderRoot($build);
    $response = new Response();
    $response->setContent($html);

    return $response;
  }

}
