<?php

namespace Drupal\girchi_referral\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Render\Renderer;
use Drupal\girchi_referral\TopReferralsService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TopReferralsController.
 */
class TopReferralsController extends ControllerBase {
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
   * Database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $database;

  /**
   * TopReferralsService.
   *
   * @var \Drupal\girchi_referral\TopReferralsService
   */
  private $topReferrals;

  /**
   * Constructs a new PartyListController object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   LoggerChannelFactory.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Renderer.
   * @param \Drupal\Core\Database\Connection $database
   *   Database.
   * @param \Drupal\girchi_referral\TopReferralsService $topReferralsService
   *   TopReferralsService.
   */
  public function __construct(LoggerChannelFactoryInterface $loggerChannelFactory,
                              Renderer $renderer,
                              Connection $database,
                              TopReferralsService $topReferralsService) {
    $this->loggerFactory = $loggerChannelFactory->get('girchi_referrals');
    $this->renderer = $renderer;
    $this->database = $database;
    $this->topReferrals = $topReferralsService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory'),
      $container->get('renderer'),
      $container->get('database'),
      $container->get('girchi_referral.top_referrals_service')
    );
  }

  /**
   * GetTopReferrals.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns html.
   */
  public function getTopReferrals(Request $request) {
    $source = $request->request->get('source');
    if (isset($source)) {
      try {
        $results = $this->topReferrals->getTopReferrals($source, TRUE);

        $build = [
          '#type' => 'markup',
          '#theme' => 'top_referrals_modal',
          '#topReferrals' => $results,
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
