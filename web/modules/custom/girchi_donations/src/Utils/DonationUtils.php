<?php

namespace Drupal\girchi_donations\Utils;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\language\ConfigurableLanguageManager;

/**
 * Utilities service for donations.
 */
class DonationUtils {
  /**
   * EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * LoggerFactory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Translation.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translationManager;

  /**
   * Language.
   *
   * @var \Drupal\language\ConfigurableLanguageManager
   */
  protected $languageManager;

  /**
   * Ged calculator.
   *
   * @var GedCalculator
   */
  public $gedCalculator;

  /**
   * Constructor for service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity Type Manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger.
   * @param \Drupal\Core\StringTranslation\TranslationManager $translationManager
   *   Translation.
   * @param \Drupal\language\ConfigurableLanguageManager $languageManager
   *   LanguageManager.
   * @param GedCalculator $gedCalculator
   *   GedCalculator.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              LoggerChannelFactoryInterface $loggerFactory,
                              TranslationManager $translationManager,
                              ConfigurableLanguageManager $languageManager,
                              GedCalculator $gedCalculator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $loggerFactory;
    $this->translationManager = $translationManager;
    $this->languageManager = $languageManager;
    $this->gedCalculator = $gedCalculator;
  }

  /**
   * Function for getting politicians.
   */
  public function getPoliticians() {

    $options = [];
    try {
      /** @var \Drupal\user\UserStorage $user_storage */
      $user_storage = $this->entityTypeManager->getStorage('user');
      $politician_ids = $user_storage->getQuery()
        ->condition('field_first_name', NULL, 'IS NOT NULL')
        ->condition('field_last_name', NULL, 'IS NOT NULL')
        ->condition('field_politician', TRUE)
        ->execute();

      $politicians = $user_storage->loadMultiple($politician_ids);

      if ($politicians) {
        /** @var \Drupal\user\Entity\User $politician */
        foreach ($politicians as $politician) {
          $options[$politician->id()] = sprintf('%s %s',
              $politician->get('field_first_name')->value,
              $politician->get('field_last_name')->value);
        }
      }

      return $options;
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());
    }

    return $options;
  }

  /**
   * Function for getting terms of donation_issues.
   */
  public function getTerms() {
    $options = [];
    try {
      /** @var \Drupal\taxonomy\TermStorage  $term_storage */
      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      $terms = $term_storage->loadTree('donation_issues', 0, NULL, TRUE);
      if ($terms) {
        /** @var \Drupal\taxonomy\Entity\Term $term */
        foreach ($terms as $term) {
          $language = $this->languageManager->getCurrentLanguage()->getId();
          if ($language === 'ka' && $term->hasTranslation('ka')) {
            $options[$term->id()] = $term->getTranslation('ka')->getName();
          }
          else {
            $options[$term->id()] = $term->getName();
          }
        }
      }
      return $options;
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());
    }

    return $options;

  }

  /**
   * Function for adding record.
   *
   * @param string $type
   *   Type.
   * @param array $donation
   *   Donation.
   * @param string $entity_id
   *   Entity id.
   *
   * @return mixed
   *   Donation.
   */
  public function addDonationRecord($type, array $donation, $entity_id) {

    // TYPE 1 - AIM
    // TYPE 2 - Politician.
    try {
      $donationStorage = $this->entityTypeManager->getStorage('donation');
      if ($type == 1) {
        $additional_fields = ['aim_donation' => TRUE, 'aim_id' => $entity_id];
      }
      else {
        $additional_fields = ['politician_donation' => TRUE, 'politician_id' => $entity_id];
      }
      $final_fields = array_merge($donation, $additional_fields);
      $entity = $donationStorage->create($final_fields);
      $entity->save();
      $this->loggerFactory->get('girchi_donations')->info('Saved to donations with Status: INITIAL');
      return $entity;
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());
    }
    catch (EntityStorageException $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());
    }

    return FALSE;
  }

  /**
   * Regular donation creation method.
   *
   * @param array $reg_donation
   *   Regular donation array.
   * @param string $entity_id
   *   Entity id.
   */
  public function addRegularDonationRecord(array $reg_donation, $entity_id) {
    // TYPE 1 - AIM
    // TYPE 2 - Politician.
    try {
      $reg_donation_storage = $this->entityTypeManager->getStorage('regular_donation');
      $type = $reg_donation['type'];
      if ($type == 1) {
        $additional_field = ['aim_id' => $entity_id];
      }
      else {
        $additional_field = ['politician_id' => $entity_id];
      }

      $final_fields = array_merge($reg_donation, $additional_field);
      $reg_donation_entity = $reg_donation_storage->create($final_fields);
      $reg_donation_entity->save();
      $this->loggerFactory->get('girchi_donations')->info('Regular donation was created.');
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());
    }
    catch (PluginNotFoundException $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());
    }
    catch (EntityStorageException $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());
    }

  }

  /**
   * Get numbers from 1-28.
   *
   * @return array
   *   an array
   */
  public function getMonthDates() {
    return [
      '1' => '1',
      '2' => '2',
      '3' => '3',
      '4' => '4',
      '5' => '5',
      '6' => '6',
      '7' => '7',
      '8' => '8',
      '9' => '9',
      '10' => '10',
      '11' => '11',
      '12' => '12',
      '13' => '13',
      '14' => '14',
      '15' => '15',
      '16' => '16',
      '17' => '17',
      '18' => '18',
      '19' => '19',
      '20' => '20',
      '21' => '21',
      '22' => '22',
      '23' => '23',
      '24' => '24',
      '25' => '26',
      '27' => '27',
      '28' => '28',
    ];
  }

}
