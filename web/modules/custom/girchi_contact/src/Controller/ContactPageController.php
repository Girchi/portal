<?php

namespace Drupal\girchi_contact\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class ContactPageController.
 */
class ContactPageController extends ControllerBase {

  /**
   * EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ContactPageController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *
   *   Entity Manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityTypeManager = $entity_manager;
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
   * Index.
   *
   * @return array
   *
   *   Return theme
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function index() {
    try {
      /** @var \Drupal\node\NodeStorage $nodeStorage */
      $nodeStorage = $this->entityTypeManager->getStorage('node');
    }
    catch (InvalidPluginDefinitionException $e) {
      throw $e;
    }
    catch (PluginNotFoundException $e) {
      throw $e;
    }

    $langCode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $offices = $nodeStorage->getQuery()
      ->condition('type', 'office')
      ->execute();
    $officeNodes = $nodeStorage->loadMultiple($offices);
    $officeCities = [];
    $officeInfo = [];

    foreach ($officeNodes as $officeNode) {
      try {
        $officeNode = $officeNode->getTranslation($langCode);
      }
      catch (\InvalidArgumentException $e) {
      }

      $title = ucfirst($officeNode->{'title'}->value);
      $city = [
        "title" => $title,
        "addr1" => $officeNode->{'field_address_1'}->value,
        "addr2" => $officeNode->{'field_address_1'}->value,
        "email" => $officeNode->{'field_email'}->value,
        "phone" => $officeNode->{'field_phone'}->value,
        "wd" => $officeNode->{'field_working_days'}->value,
        "wh" => $officeNode->{'field_working_hours'}->value,
        "wwd" => $officeNode->{'field_weekend_working_days'}->value,
        "wwh" => $officeNode->{'field_weekend_working_hours'}->value,
        "map" => $officeNode->{'field_map'}->get(0)->view(),
      ];

      if (!in_array($title, $officeCities)) {
        array_push($officeCities, $title);
      }
      $officeInfo[$title][] = $city;
    }

    return [
      '#theme' => 'girchi_contact',
      '#cities' => $officeCities,
      '#offices' => $officeInfo,
    ];
  }

}
