<?php

namespace Drupal\girchi_leaderboard;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * LeadPartnersService.
 */
class LeadPartnersService {
  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
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
  public function __construct(EntityTypeManager $entity_type_manager,
                              LoggerChannelFactory $loggerFactory,
                              Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $loggerFactory->get('girchi_leaderboard');
    $this->database = $database;

  }

  /**
   * GetLeadPartners.
   *
   * @param string $source
   *   Source.
   * @param bool $full
   *   Full.
   *
   * @return array
   *   Return array.
   */
  public function getLeadPartners($source, $full) {
    try {
      $this->database->query("SET SQL_MODE=''");
      $query = $this->database->select('donation', 'dn');
      $query->leftJoin('user__field_first_name', 'fn', 'dn.user_id = fn.entity_id');
      $query->leftJoin('user__field_last_name', 'ln', 'dn.user_id = ln.entity_id');
      $query->leftJoin('user__field_publicity', 'pb', 'dn.user_id = pb.entity_id');
      $query->leftJoin('user__user_picture', 'up', 'dn.user_id = up.entity_id');
      $query->addField('fn', 'field_first_name_value', 'user_name');
      $query->addField('ln', 'field_last_name_value', 'user_surname');
      $query->addField('up', 'user_picture_target_id', 'img');
      $query->addExpression('sum(dn.amount)', 'donation');
      $query->addExpression('dn.user_id', 'uid');

      if ($full === FALSE) {
        $query->range(0, 5);
      }

      $query
        ->groupBy("user_id")
        ->orderBy('donation', 'DESC')
        ->condition('dn.status', 'OK')
        ->condition('dn.user_id', '0', '!=')
        ->condition('fn.field_first_name_value', NULL, 'IS NOT NULL')
        ->condition('ln.field_last_name_value', NULL, 'IS NOT NULL')
        ->condition('pb.field_publicity_value', '1', '=');

      if ($source === 'daily') {
        $group = $query
          ->andConditionGroup()
          ->condition('created', strtotime("now"), '<')
          ->condition('created', strtotime("-1 days"), '>');
        $query->condition($group);
        $results = $query->execute()->fetchAll();
      }
      elseif ($source === 'weekly') {
        $group = $query
          ->andConditionGroup()
          ->condition('created', strtotime("now"), '<')
          ->condition('created', strtotime("-1 week"), '>');
        $query->condition($group);
        $results = $query->execute()->fetchAll();
      }
      elseif ($source === 'monthly') {
        $group = $query
          ->andConditionGroup()
          ->condition('created', strtotime("now"), '<')
          ->condition('created', strtotime("-1 month"), '>');
        $query->condition($group);
        $results = $query->execute()->fetchAll();
      }
      else {
        $results = $query->execute()->fetchAll();
      }

      $file_storage = $this->entityTypeManager->getStorage('file');
      foreach ($results as $result) {
        if (!empty($result->img)) {
          $img_id = $result->img;
          /** @var \Drupal\file\Entity\File $img */
          $img = $file_storage->load($img_id);
          $result->img = $img->getFileUri();
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
