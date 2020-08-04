<?php

namespace Drupal\girchi_leaderboard\Plugin\Block;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\girchi_leaderboard\LeadPartnersService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a '.LeadPartner' block.
 *
 * @Block(
 *  id = "lead_partner",
 *  admin_label = @Translation("Lead partner"),
 * )
 */
class LeadPartner extends BlockBase implements ContainerFactoryPluginInterface {
  private const DONATION_ALL = 'full';
  private const DONATION_DAILY = 'daily';
  private const DONATION_WEEKLY = 'weekly';
  private const DONATION_MONTHLY = 'monthly';

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;


  /**
   * LeadPartnersService.
   *
   * @var \Drupal\girchi_leaderboard\LeadPartnersService
   */
  protected $leadPartners;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              LoggerChannelFactory $loggerFactory,
                              LeadPartnersService $leadPartnersService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->loggerFactory = $loggerFactory->get('girchi_leaderboard');
    $this->leadPartners = $leadPartnersService;

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
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('girchi_leaderboard.get_lead_partners')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $all_donations = $this->getDonations();
    $daily_donations = $this->getDonations(self::DONATION_DAILY);
    $weekly_donations = $this->getDonations(self::DONATION_WEEKLY);
    $monthly_donations = $this->getDonations(self::DONATION_MONTHLY);
    return [
      '#theme' => 'lead_partners',
      '#leadPartner' => $all_donations,
      '#leadPartnerDaily' => $daily_donations,
      '#leadPartnerWeekly' => $weekly_donations,
      '#leadPartnerMonthly' => $monthly_donations,
    ];
  }

  /**
   * {@inheritdoc}
   */
  private function getDonations($mode = self::DONATION_ALL) {
    try {
      return $this->leadPartners->getLeadPartners($mode, FALSE);
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->error($e->getMessage());
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
