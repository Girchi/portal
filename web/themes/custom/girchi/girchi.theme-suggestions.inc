<?php

/**
 * @file
 * Theme suggestions.
 */

use Drupal\block\Entity\Block;

/**
 * Implements hook_theme_suggestions_alter().
 */
function girchi_theme_suggestions_alter(array &$suggestions, array $variables, $hook) {
  if ($hook == 'form' && !empty($variables['element']['#theme'][0])) {
    $suggestions[] = 'form__' . $variables['element']['#theme'][0];
  }
}

/**
 * Implements hook_theme_suggestions_user_alter().
 */
function girchi_theme_suggestions_user_alter(&$hooks, $vars) {
  $view_mode = $vars['elements']['#view_mode'];
  $hooks[] = 'user__' . $view_mode;
}

/**
 * Implements hook_theme_suggestions_block_alter().
 */
function girchi_theme_suggestions_block_alter(&$hooks, &$vars) {
  $no_markup_tpl = 'block__no_markup';
  if (!empty($vars['elements']['#id'])) {
    $block_id = $vars['elements']['#id'];
    $block = Block::load($block_id);

    // Region.
    $region = $block->getRegion();
    $hooks[] = 'block__' . $region;
    $hooks[] = 'block__' . $block->getRegion() . '__' . $block_id;

    // View mode.
    if (!empty($vars['elements']['#configuration']['view_mode'])) {
      $view_mode = $vars['elements']['#configuration']['view_mode'];
      $hooks[] = 'block__' . $view_mode;
      $hooks[] = 'block__' . $view_mode . '__' . $block_id;
    }

    // Custom Blocks (Bundles)
    if ($vars['elements']['#base_plugin_id'] == 'block_content') {
      // Bundle type.
      $bundle = $vars['elements']['content']['#block_content']->bundle();
      $hooks[] = 'block__' . $bundle;
      $hooks[] = 'block__' . $bundle . '__' . $view_mode;
    }

    // Base plugin ID.
    switch ($vars['elements']['#base_plugin_id']) {
      case 'system_menu_block':
        // $hooks[] = $no_markup_tpl;
        break;
    }

    // ID.
    switch ($block_id) {
      case 'girchi_page_title':
      case 'girchi_content':
        $hooks[] = $no_markup_tpl;
        break;
    }
  }
}

/**
 * Implements hook_theme_suggestions_menu_alter().
 */
function girchi_theme_suggestions_menu_alter(&$hooks, &$vars) {

  // By region.
  if (isset($vars['attributes']['region'])) {
    $region = $vars['attributes']['region'];
    $hooks[] = 'menu__' . $region;
  }

}

/**
 * Implements hook_theme_suggestions_field_alter().
 */
function girchi_theme_suggestions_field_alter(array &$suggestions, array $variables) {

  $element = $variables['element'];
  $suggestions[] = 'field__' . $element['#view_mode'];
  $suggestions[] = 'field__' . $element['#view_mode'] . '__' . $element['#field_name'];

}

/**
 * Implements hook_theme_suggestions_views_view_alter().
 */
function girchi_theme_suggestions_views_view_alter(array &$suggestions, array $variables) {
  $suggestions[] = sprintf('views_view__%s_%s', $variables['view']->id(), $variables['view']->current_display);

}

/**
 * Implements hook_theme_suggestions_details_alter().
 */
function girchi_theme_suggestions_details_alter(array &$suggestions, array $variables) {

}

/**
 * Implements hook_theme_suggestions_node_alter().
 */
function girchi_theme_suggestions_node_alter(array &$suggestions, array $variables) {

  // Node theme suggestion by alias.
  if (!empty($variables['elements']['#view_mode']) && $variables['elements']['#view_mode'] == 'full') {
    $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $variables['elements']['#node']->id());
    $alias = ltrim($alias, '/');
    $suggestions[] = 'node__' . str_replace('/', '-', $alias);
  }

}

/**
 * Implements hook_theme_suggestions_taxonomy_term_alter().
 */
function girchi_theme_suggestions_taxonomy_term_alter(array &$suggestions, array $variables) {
  /** @var \Drupal\taxonomy\TermInterface $term */
  $term = $variables['elements']['#taxonomy_term'];
  $sanitized_view_mode = strtr($variables['elements']['#view_mode'], '.', '_');
  // Add view mode theme suggestions.
  $suggestions[] = 'taxonomy_term__' . $sanitized_view_mode;
  $suggestions[] = 'taxonomy_term__' . $term->bundle() . '__' . $sanitized_view_mode;
  $suggestions[] = 'taxonomy_term__' . $term->id() . '__' . $sanitized_view_mode;
}

/**
 * Implements hook_theme_suggestions_page_alter().
 */
function girchi_theme_suggestions_page_alter(array &$suggestions, array $variables) {
  // Add content type suggestions.
  if ($node = \Drupal::request()->attributes->get('node')) {
    array_splice($suggestions, 1, 0, 'page__node__' . $node->getType());
  }
}
