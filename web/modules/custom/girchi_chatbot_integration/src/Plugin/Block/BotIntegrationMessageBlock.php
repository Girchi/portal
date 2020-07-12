<?php

namespace Drupal\girchi_chatbot_integration\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a bot integration message block.
 *
 * @Block(
 *   id = "girchi_chatbot_integration_bot_integration_message",
 *   admin_label = @Translation("Bot Integration Message"),
 *   category = @Translation("Custom")
 * )
 */
class BotIntegrationMessageBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new BotIntegrationMessageBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityStorageInterface $user_manager
   *   User manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user, EntityStorageInterface $user_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->userManager = $user_manager;
  }

  /**
   * User manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if (!$account->id()) {
      return AccessResult::forbidden();
    }

    $user = $this->userManager->load($this->currentUser->id());
    if (isset($user->get('field_connected_with_bot')->getValue()[0]['value'])) {
      return AccessResult::forbidden();;
    }
    else {
      return AccessResult::allowed();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $user = $this->userManager->load($this->currentUser->id());
    if ($user->get('field_bot_integration_code')) {
      $code = $user->get('field_bot_integration_code')->getValue()[0]['value'];
    }
    $build['content'] = [
      '#markup' => $this->t('Connect with Girchi Bot. Send @code and you are connected.', [
        '@code' => $code,
      ]),
    ];
    return $build;
  }

}
