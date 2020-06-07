<?php

namespace Drupal\girchi_reports\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class UsersReportController.
 */
class UsersReportController extends ControllerBase {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->database = $container->get('database');
    return $instance;
  }

  /**
   * Users-report.
   *
   * @return string
   *   Return Hello string.
   */
  public function users() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: users-report'),
    ];
  }

}
