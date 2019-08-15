<?php

namespace Drupal\girchi_my_party_list;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\Entity\File;
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
   * Constructs a new PartyListCalculatorService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *
   *   Logger factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *
   *   Request stack;.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RequestStack $requestStack) {
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $requestStack->getCurrentRequest();
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
      $user_storage = $this->entityTypeManager->getStorage('user');
      $user_ids = $user_storage->getQuery()
        ->condition('field_ged', '0', '>')
        ->condition('field_my_party_list', '0', '>')
        ->execute();
      $users = $user_storage->loadMultiple($user_ids);
      /**
       * @var \Drupal\user\Entity\User $user
       */
      if (!empty($users)) {
        foreach ($users as $user) {

          $user_party_list = $user->get('field_my_party_list')->getValue();
          $user_ged = (int) $user->get('field_ged')->getValue()[0]['value'];
          foreach ($user_party_list as $party_list_item) {
            $percentage = (int) $party_list_item['value'];
            $uid = $party_list_item['target_id'];
            if (isset($user_rating[$uid])) {
              $user_rating[$uid] += $user_ged * ($percentage / 100);
            }
            else {
              $user_rating[$uid] = $user_ged * ($percentage / 100);
            }
          };
        }
        arsort($user_rating);
        $rating_number = 1;
        foreach ($user_rating as $uid => $ged_amount) {
          /**
           * @var \Drupal\user\Entity\User $politician
           */
          $politician = $user_storage->load($uid);
          if ($politician != NULL) {
            $politician->set('field_rating_in_party_list', $rating_number);
            $politician->set('field_political_ged', $ged_amount);
            try {
              $politician->save();
              $rating_number++;
            }
            catch (EntityStorageException $e) {
              $this->loggerFactory->error($e->getMessage());
            }
          }
        }
      }
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
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
      ->execute();

    /** @var User $users */
    $users = $storage->loadMultiple($users);

    /** @var EntityReferenceFieldItemListAlias $field */
    foreach ($users as $user) {
      if (!empty($user->get('user_picture')[0])) {
        $img_id = $user->get('user_picture')[0]->getValue()['target_id'];
        $img_file = File::load($img_id);
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
