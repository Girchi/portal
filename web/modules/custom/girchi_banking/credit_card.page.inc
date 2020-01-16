<?php

/**
 * @file
 * Contains credit_card.page.inc.
 *
 * Page callback for Credit card entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Credit card templates.
 *
 * Default template: credit_card.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_credit_card(array &$variables) {
  // Fetch CreditCard Entity Object.
  // $credit_card = $variables['elements']['#credit_card'];.
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
