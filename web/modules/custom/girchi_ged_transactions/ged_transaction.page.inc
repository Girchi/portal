<?php

/**
 * @file
 * Contains ged_transaction.page.inc.
 *
 * Page callback for GED transaction entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for GED transaction templates.
 *
 * Default template: ged_transaction.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_ged_transaction(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
