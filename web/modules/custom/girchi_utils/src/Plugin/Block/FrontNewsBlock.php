<?php

namespace Drupal\girchi_utils\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Provides a 'Front news block' block.
 *
 * @Block(
 *  id = "front_news_block",
 *  admin_label = @Translation("Front news block"),
 * )
 */
class FrontNewsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $vid = 'news_categories';
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
    $term_data = [];
    foreach ($terms as $term) {
      $term_data[$term->tid] = $term->name;
    }
    $term_data = ['all' => t('All')] + $term_data;
    $form['category_select'] = [
      '#type' => 'select',
      '#title' => $this->t('Select category'),
      '#options' => $term_data,
      '#default_value' => isset($this->configuration['category_select']) ? $this->configuration['category_select'] : 1,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['category_select'] = $form_state->getValue('category_select');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $category_id = $this->configuration['category_select'];
    $em = \Drupal::entityTypeManager();

    /** @var \Drupal\node\Entity\NodeStorage $node_storage */
    $node_storage = $em->getStorage('node');
    if ($category_id == 'all') {
      $lastest_articles = $node_storage->getQuery()
        ->condition('type', 'article')
        ->condition('status', 1)
        ->condition('field_is_video', '0')
        ->sort('created', "DESC")
        ->range(0, 10)
        ->execute();
    }
    else {
      $lastest_articles = $node_storage->getQuery()
        ->condition('type', 'article')
        ->condition('status', 1)
        ->condition('field_category', $category_id, '=')
        ->sort('created', "DESC")
        ->range(0, 10)
        ->execute();
    }

    $articles = Node::loadMultiple($lastest_articles);
    krsort($articles);

    $template = [
      '#theme' => 'front_page_articles',
      '#articles' => $articles,
    ];

    $category = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($category_id);
    if ($category) {
      $template['#category'] = $category->getName();
    }

    return $template;

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['node_list']);
  }

}
