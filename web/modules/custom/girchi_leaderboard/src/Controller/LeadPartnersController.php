<?php

namespace Drupal\girchi_leaderboard\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Render\Renderer;
use Drupal\girchi_leaderboard\LeadPartnersService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * LeadPartnersController.
 */
class LeadPartnersController extends ControllerBase {

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Drupal\Core\Render\Renderer definition.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * LeadPartnersService.
   *
   * @var \Drupal\girchi_leaderboard\LeadPartnersService
   */
  protected $leadPartners;

  /**
   * Constructs a new PartyListController object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   LoggerChannelFactory.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Renderer.
   * @param \Drupal\girchi_leaderboard\LeadPartnersService $leadPartnersService
   *   LeadPartnersService.
   */
  public function __construct(LoggerChannelFactoryInterface $loggerChannelFactory,
                              Renderer $renderer,
                              LeadPartnersService $leadPartnersService) {
    $this->loggerFactory = $loggerChannelFactory->get('girchi_leaderboard');
    $this->renderer = $renderer;
    $this->leadPartners = $leadPartnersService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory'),
      $container->get('renderer'),
      $container->get('girchi_leaderboard.get_lead_partners')
    );
  }

  /**
   * GetLeadPartners.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns html.
   */
  public function getLeadPartners(Request $request) {
    $source = $request->request->get('source');
    if (isset($source)) {
      try {
        $result = $this->leadPartners->getLeadPartners($source, TRUE);
        $build = [
          '#type' => 'markup',
          '#theme' => 'lead_partners_modal',
          '#final_partners' => $result,
        ];
        $html = $this->renderer->renderRoot($build);

        return new JsonResponse(['status' => 'success', 'data' => $html]);
      }
      catch (InvalidPluginDefinitionException $e) {
        $this->loggerFactory->error($e->getMessage());
      }
      catch (PluginNotFoundException $e) {
        $this->loggerFactory->error($e->getMessage());
      }
    }

    return new JsonResponse(["status" => 'Invalid source']);
  }

}
