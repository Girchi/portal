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
      $politicians = $user_storage->loadByProperties(['field_politician' => TRUE]);

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

  public function addDonationRecord($type, $donation, $entity_id) {
    /**
     * TYPE 1 - AIM
     * TYPE 2 - Politician
     */
    try {
      $donationStorage = $this->entityTypeManager->getStorage('donation');
      if ($type === 1) {
        $additional_fields = ['aim_donation' => TRUE, 'aim_id' => $entity_id];
      }else {
        $additional_fields = ['politician_donation' => TRUE, 'politician_id'=>$entity_id];
      }
      $final_fields = array_merge($donation, $additional_fields);
      $entity = $donationStorage->create($final_fields);
      $entity->save();
      $this->loggerFactory->get('girchi_donations')->info('Saved to donations with Status: INITIAL');
      return true;
    } catch (InvalidPluginDefinitionException $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());
    } catch (PluginNotFoundException $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());
    } catch (EntityStorageException $e) {
      $this->loggerFactory->get('girchi_donations')->error($e->getMessage());
    }

    return false;
  }

}
