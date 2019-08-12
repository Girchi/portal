<?php

/**
 * @file
 * Contains donation.page.inc.
 *
 * Page callback for Donation entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Donation templates.
 *
 * Default template: donation.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_donation(array &$variables) {
  // Fetch Donation Entity Object.
  $donation = $variables['elements']['#donation'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
