<?php

namespace Drupal\girchi_leaderboard\Plugin\Block;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
  private const DONATION_ALL = 0;
  private const DONATION_DAILY = 1;
  private const DONATION_WEEKLY = 2;
  private const DONATION_MONTHLY = 3;

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
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManager $entity_type_manager, LoggerChannelFactory $loggerFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $loggerFactory->get('girchi_leaderboard');

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
      $container->get('entity_type.manager'),
      $container->get('logger.factory')
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
      $user_storage = $this->entityTypeManager->getStorage('user');
      $donation_storage = $this->entityTypeManager->getStorage('donation');

      $donation_entity_ids_query = $donation_storage->getQuery()
        ->condition('status', 'OK')
        ->condition('user_id', '0', '!=')
        ->sort('amount', 'DESC');

      if ($mode === self::DONATION_DAILY) {
        $group = $donation_entity_ids_query
          ->andConditionGroup()
          ->condition('created', strtotime("now"), '<')
          ->condition('created', strtotime("-1 days"), '>');
        $donation_entity_ids_query->condition($group);
      }
      elseif ($mode === self::DONATION_WEEKLY) {
        $group = $donation_entity_ids_query
          ->andConditionGroup()
          ->condition('created', strtotime("now"), '<')
          ->condition('created', strtotime("-1 week"), '>');
        $donation_entity_ids_query->condition($group);
      }
      elseif ($mode === self::DONATION_MONTHLY) {
        $group = $donation_entity_ids_query
          ->andConditionGroup()
          ->condition('created', strtotime("now"), '<')
          ->condition('created', strtotime("-1 month"), '>');
        $donation_entity_ids_query->condition($group);
      }

      $donation_entity_ids_query->range(0, 5);
      $donation_entity_ids = $donation_entity_ids_query->execute();

      $top_partners = $donation_storage->loadMultiple($donation_entity_ids);
      $final_partners = [];
      /** @var \Drupal\girchi_donations\Entity\Donation $top_partner */
      foreach ($top_partners as $top_partner) {
        if ($top_partner->getUser()) {
          $donation_amount = $top_partner->getAmount();
          $uid = $top_partner->getUser()->id();
          $user = $user_storage->load($uid);
          $user_name = $user->get('field_first_name')->value;
          $user_surname = $user->get('field_last_name')->value;
          $publicity = $user->get('field_publicity')->value;
          if ($user->get('user_picture')->entity) {
            $profilePictureEntity = $user->get('user_picture')->entity;
            $profilePicture = $profilePictureEntity->getFileUri();
          }
          else {
            $profilePicture = NULL;
          }
          if (empty($user_name) || empty($user_surname)) {
            continue;
          }
          elseif ($publicity != 1) {
            continue;
          }
          if (array_key_exists($uid, $final_partners)) {
            $final_partners[$uid]['donation'] += $donation_amount;
          }
          else {
            $final_partners[$uid] = [
              'uid' => $uid,
              'user_name' => $user_name,
              'user_surname' => $user_surname,
              'donation' => $donation_amount,
              'img' => $profilePicture,
            ];
          }
        }
      }
      usort($final_partners, function ($a, $b) {
        return $b['donation'] - $a['donation'];
      });
      return $final_partners;
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
