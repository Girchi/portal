<?php

namespace Drupal\girchi_utils\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;

/**
 * Class SupportersRegistrationController.
 */
class SupportersRegistrationController extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityFormBuilderInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  protected $formBuilder;

  /**
   * Constructs a new SupportersRegistrationController object.
   *
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *
   *   Entity Form builder.
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *
   *   Form builder.
   */
  public function __construct(EntityFormBuilderInterface $entity_form_builder,
                              FormBuilder $formBuilder) {
    $this->entityFormBuilder = $entity_form_builder;
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.form_builder'),
      $container->get('form_builder')
    );
  }

  /**
   * Registerpage.
   *
   * @return array|string
   *   Return Hello string.
   */
  public function registerPage() {

    $form = $this->entityFormBuilder->getForm(User::create([]),
      'register');

    unset($form['field_personal_id']['widget']['0']['value']['#title']);
    unset($form['field_first_name']['widget']['0']['value']['#title']);
    unset($form['field_last_name']['widget']['0']['value']['#title']);
    unset($form['field_date_of_birth']['widget']['0']['value']['#title']);
    unset($form['field_phone']['widget']['0']['value']['#title']);
    unset($form['account']['mail']['#title']);
    unset($form['account']['mail']['#description']);
    unset($form['account']['pass']['pass1']['#title']);
    unset($form['account']['pass']['pass2']['#title']);
    unset($form['field_ged']['widget']['0']['value']['#title']);

    $form['field_personal_id']['widget'][0]['value']['#attributes']['class'][] = 'form-control form-control-lg fix';
    $form['field_first_name']['widget'][0]['value']['#attributes']['class'][] = 'form-control form-control-lg fix';
    $form['field_last_name']['widget'][0]['value']['#attributes']['class'][] = 'form-control form-control-lg fix';
    $form['field_phone']['widget'][0]['value']['#attributes']['class'][] = 'form-control form-control-lg fix';
    $form['account']['mail']['#attributes']['class'][] = 'form-control form-control-lg fix';
    $form['account']['pass']['pass1']['#attributes']['class'][] = 'form-control form-control-lg fix';
    $form['account']['pass']['pass2']['#attributes']['class'][] = 'form-control form-control-lg fix';
    $form['field_ged']['widget'][0]['value']['#attributes']['class'][] = 'form-control form-control-lg fix';
    $form['actions']['submit']['#attributes']['class'][] = 'btn btn-lg btn-block btn-warning text-uppercase mt-4';

    return [
      '#theme' => 'supporter_registration',
      '#form' => $form,
    ];
  }

}
