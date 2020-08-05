<?php

namespace Drupal\girchi_referral;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Class TopReferralsService.
 */
class TopReferralsService {
  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $database;

  /**
   * {@inheritDoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              LoggerChannelFactoryInterface $loggerFactory,
                              Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $loggerFactory->get('girchi_referral');
    $this->database = $database;
  }

  /**
   * GetTopReferrals.
   *
   * @param string $source
   *   Source.
   * @param bool $full
   *   Full list of top referrals.
   *   Will be true for modal usage only.
   *
   * @return array
   *   Result.
   */
  public function getTopReferrals($source, $full) {
    try {
      $results = [];
      if ($source == 'full') {
        $this->database->query("SET SQL_MODE=''");
        $query = $this->database->select('user__field_referral_benefits', 'rb');
        $query->leftJoin('user__field_first_name', 'fn', 'rb.entity_id = fn.entity_id');
        $query->leftJoin('user__field_last_name', 'ln', 'rb.entity_id = ln.entity_id');
        $query->leftJoin('user__field_publicity', 'pb', 'rb.entity_id = pb.entity_id');
        $query->leftJoin('user__user_picture', 'up', 'rb.entity_id = up.entity_id');
        $query->addField('fn', 'field_first_name_value', 'user_name');
        $query->addField('ln', 'field_last_name_value', 'user_surname');
        $query->addField('up', 'user_picture_target_id', 'img');
        $query->addField('rb', 'entity_id', 'uid');
        $query->addExpression('rb.field_referral_benefits_value', 'referral_benefits');
        if ($full === FALSE) {
          $query->range(0, 5);
        }
        $query
          ->orderBy('field_referral_benefits_value', 'DESC')
          ->condition('rb.entity_id', '0', '!=')
          ->condition('rb.field_referral_benefits_value', '0', '>')
          ->condition('fn.field_first_name_value', NULL, 'IS NOT NULL')
          ->condition('ln.field_last_name_value', NULL, 'IS NOT NULL')
          ->condition('pb.field_publicity_value', '1', '=');
        $results = $query->execute()->fetchAll();
      }
      elseif ($source == "monthly") {
        $this->database->query("SET SQL_MODE=''");
        $query = $this->database->select('node__field_user', 'rt');
        $query->leftJoin('node__field_referral', 'fr', 'rt.entity_id = fr.entity_id');
        $query->leftJoin('node__field_donation', 'fd', 'rt.entity_id = fd.entity_id');
        $query->leftJoin('donation', 'do', 'fd.field_donation_target_id = do.id');
        $query->leftJoin('node__field_amount_of_money', 'fa', 'rt.entity_id = fa.entity_id');
        $query->leftJoin('user__field_first_name', 'fn', 'fr.field_referral_target_id = fn.entity_id');
        $query->leftJoin('user__field_last_name', 'ln', 'fr.field_referral_target_id = ln.entity_id');
        $query->leftJoin('user__field_publicity', 'pb', 'fr.field_referral_target_id = pb.entity_id');
        $query->leftJoin('user__user_picture', 'up', 'fr.field_referral_target_id = up.entity_id');
        $query->addField('fn', 'field_first_name_value', 'user_name');
        $query->addField('ln', 'field_last_name_value', 'user_surname');
        $query->addField('up', 'user_picture_target_id', 'img');
        $query->addField('fr', 'field_referral_target_id', 'uid');
        $query->addExpression('sum(fa.field_amount_of_money_value)', 'referral_benefits');
        $query->addExpression('fr.field_referral_target_id', 'uid');
        if ($full === FALSE) {
          $query->range(0, 5);
        }
        $query
          ->groupBy("uid")
          ->orderBy('referral_benefits', 'DESC')
          ->condition('fn.field_first_name_value', NULL, 'IS NOT NULL')
          ->condition('ln.field_last_name_value', NULL, 'IS NOT NULL')
          ->condition('pb.field_publicity_value', '1', '=')
          ->condition('created', strtotime("first day of this month"), '>=')
          ->condition('created', strtotime("last day of this month"), '<=');
        $results = $query->execute()->fetchAll();
      }

      $user_storage = $this->entityTypeManager->getStorage('user');
      $file_storage = $this->entityTypeManager->getStorage('file');
      foreach ($results as $result) {
        if (!empty($result->img)) {
          $img_id = $result->img;
          /** @var \Drupal\file\Entity\File $img */
          $img = $file_storage->load($img_id);
          $result->img = $img->getFileUri();
        }
        if ($full === TRUE) {
          // Get user referrals info.
          $uid = $result->uid;
          $referral_id = $user_storage->getQuery()
            ->condition('field_referral', $uid, '=')
            ->condition('field_first_name', NULL, 'IS NOT NULL')
            ->condition('field_last_name', NULL, 'IS NOT NULL')
            ->range(0, 5)
            ->execute();
          $ref_count = $user_storage->getQuery()
            ->condition('field_referral', $uid, '=')
            ->condition('field_first_name', NULL, 'IS NOT NULL')
            ->condition('field_last_name', NULL, 'IS NOT NULL')
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
          $result->referrals = $refs;
          $result->referral_count = $ref_count;
        }
      }
      return $results;
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->error($e->getMessage());
    }
    return [];
  }

}
