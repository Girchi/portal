<?php

namespace Drupal\girchi_donations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ExportController.
 */
class ExportController extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->loggerFactory = $container->get('logger.factory');
    return $instance;
  }

  /**
   * Exportpage.
   *
   * @return array
   *   Return Hello string.
   */
  public function exportPage() {

    return [
      '#type' => 'markup',
      '#theme' => 'girchi_donations_export',
      '#attached' => [
        'library' => [
          'girchi_donations/react-export',
        ],
      ],
    ];
  }

}
