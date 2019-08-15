<?php

namespace Drupal\girchi_users\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'ged_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "ged_formatter",
 *   label = @Translation("GED Formatter"),
 *   field_types = {
 *     "text",
 *     "text_plain",
 *     "string",
 *   }
 * )
 */
class GEDFormatter extends FormatterBase
{

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings()
  {
    return [
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state)
  {
    return [
      // Implement settings form.
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary()
  {
    $summary = [];
    // Implement settings summary.

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode)
  {
    $GEDHelper = \Drupal::service('girchi_users.ged_helper');

    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => $GEDHelper::getFormattedGED($this->viewValue($item))];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item)
  {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }
}
