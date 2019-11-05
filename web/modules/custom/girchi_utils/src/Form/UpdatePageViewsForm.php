<?php

namespace Drupal\girchi_utils\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * UpdatePageViewsForm.
 */
class UpdatePageViewsForm extends FormBase {
  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * UpdatePageViewsForm Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'update_page_views_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['update'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update node views'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $em = $this->entityTypeManager;

    /** @var \Drupal\node\Entity\NodeStorage $node_storage */
    $node_storage = $em->getStorage('node');
    $nids = $node_storage->getQuery()
      ->condition('type', 'article')
      ->execute();

    $batch = [
      'title' => $this->t('Updating article...'),
      'operations' => [
        [
          '\Drupal\girchi_utils\UpdatePageViews::updateViews',
          [$nids],
        ],
      ],
      'finished' => '\Drupal\girchi_utils\UpdatePageViews::updateViewsFinishedCallback',
    ];

    batch_set($batch);
  }

}
