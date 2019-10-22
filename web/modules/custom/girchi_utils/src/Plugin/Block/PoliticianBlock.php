<?php

namespace Drupal\girchi_utils\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

/**
 * Provides a 'PoliticianBlock' block.
 *
 * @Block(
 *  id = "politician_block",
 *  admin_label = @Translation("Politician block"),
 * )
 */
class PoliticianBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;
  /**
   * Drupal\Core\Session\AccountProxyInterface.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $accountProxy;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    LanguageManagerInterface $languageManager,
    EntityTypeManager $entity_type_manager,
    AccountProxy $accountProxy) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageManager = $languageManager;
    $this->entityTypeManager = $entity_type_manager;
    $this->accountProxy = $accountProxy;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('current_user')

    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $language = $this->languageManager->getCurrentLanguage()->getId();

    /** @var \Drupal\user\UserStorage $user_storage */
    try {
      $user_storage = $this->entityTypeManager->getStorage('user');
      $uid = $this->accountProxy->getAccount()->id();
      $user = $user_storage->load($uid);
      $is_politician = $user->get('field_politician')->value;
    }
    catch (InvalidPluginDefinitionException $e) {
    }
    catch (PluginNotFoundException $e) {
    }

    return [
      '#theme' => 'politician_block',
      '#is_politician' => $is_politician,
      '#language' => $language,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
