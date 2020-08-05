<?php

namespace Drupal\girchi_referral\Plugin\Block;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\girchi_referral\TopReferralsService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'TopReferrals' block.
 *
 * @Block(
 *  id = "top_referrals",
 *  admin_label = @Translation("Top referrals"),
 * )
 */
class TopReferrals extends BlockBase implements ContainerFactoryPluginInterface {
  private const REFERRALS_ALL = 'full';
  private const REFERRALS_MONTHLY = 'monthly';

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
   * TopReferralsService.
   *
   * @var \Drupal\girchi_referral\TopReferralsService
   */
  private $topReferrals;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entity_type_manager,
                              LoggerChannelFactoryInterface $loggerFactory,
                              Connection $connection,
                              TopReferralsService $topReferralsService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $loggerFactory->get('girchi_referrals');
    $this->database = $connection;
    $this->topReferrals = $topReferralsService;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return \Drupal\girchi_referral\Plugin\Block\TopReferrals
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $container->get('database'),
      $container->get('girchi_referral.top_referrals_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $all_referrals = $this->getReferrals();
    $monthly_referrals = $this->getReferrals(self::REFERRALS_MONTHLY);

    return [
      '#theme' => 'top_referrals',
      '#topReferrals' => $all_referrals,
      '#topReferralsMonthly' => $monthly_referrals,
    ];

  }

  /**
   * GetReferrals.
   *
   * @param int $mode
   *   Mode.
   *
   * @return array|array[]
   *   Top referrals.
   */
  public function getReferrals($mode = self::REFERRALS_ALL) {
    try {
      return $this->topReferrals->getTopReferrals($mode, FALSE);
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->error($e->getMessage());

    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->error($e->getMessage());
    }

    return [
      '#topReferrals' => [],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['node_list']);
  }

}
