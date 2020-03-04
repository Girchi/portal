<?php

namespace Drupal\girchi_donations\Controller;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Drupal\Core\Controller\ControllerBase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ExportController.
 */
class ExportController extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Export donation service.
   *
   * @var \Drupal\girchi_donations\ExportDonationService
   */
  protected $exportDonationService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->loggerFactory = $container->get('logger.factory');
    $instance->exportDonationService = $container->get('girchi_donations.export_donation');
    return $instance;
  }

  /**
   * Exportpage.
   *
   * @return array
   *   Return Hello string.
   */
  public function exportPage() {

    return [
      '#type' => 'markup',
      '#theme' => 'girchi_donations_export',
      '#attached' => [
        'library' => [
          'girchi_donations/react-export',
        ],
      ],
    ];
  }

  /**
   * Function to get donation resource.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse|Response
   *   Response.
   */
  public function getResource(Request $request) {
    try {
      $year = $request->query->get('year');
      $months = $request->query->get('months');
      $donation_source = $request->query->get('donation_source');

      $spreadsheet = new Spreadsheet();

      $explode_month = explode(':', $months);
      $first_month = $explode_month[0];
      $last_month = $explode_month[1];
      $start_month = Carbon::parse("${first_month}/01/${year}");
      $end_month = Carbon::parse("${last_month}/01/${year}")->endOfMonth();

      $period = CarbonPeriod::create($start_month, '1 month', $end_month);
      $months = [];
      /** @var \Carbon\Carbon $dt */
      foreach ($period as $dt) {
        $key = $dt->format('m');
        $months[$key] =
          $dt->format('F');
      }

      $donation_records = $this->exportDonationService->exportDonationService($start_month->timestamp, $end_month->timestamp, $donation_source);

      // Print months in table.
      $spreadsheet->getActiveSheet()
        ->fromArray($months, NULL, 'B1');
      $spreadsheet->getActiveSheet();

      $json_format = [];
      $cell_value = 2;
      foreach ($donation_records as $donation_record) {
        // Print users in table.
        $spreadsheet->getActiveSheet()
          ->setCellValue("A${cell_value}", $donation_record['full_name']);
        $spreadsheet->getActiveSheet();

        foreach ($months as $key => $month) {
          // If donation was not created in any month
          // table will print 0 in that column.
          if (!array_key_exists($key, $donation_record['donation'])) {
            $donation_record['donation'][$key] = '0';
          }
          ksort($donation_record['donation']);
          // Print amount of money in table.
          $spreadsheet->getActiveSheet()
            ->fromArray($donation_record['donation'], NULL, "B${cell_value}");
          $spreadsheet->getActiveSheet();

          $json_format[$donation_record['full_name']] = $donation_record['donation'];
        }
        $cell_value++;
      };
      $spreadsheet->getActiveSheet()->getColumnDimension('A')->setAutoSize(TRUE);

      if ('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' === $request->headers->get('accept')) {
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $excelContent = ob_get_clean();

        return new Response(
          $excelContent,
          Response::HTTP_OK,
          [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => sprintf('attachment; filename="%s.xlsx"', 'donation export'),
            'Access-Control-Allow-Origin' => '*',
          ]
        );
      }

      return new JsonResponse($json_format);
    }
    catch (\Exception $e) {
      $this->getLogger($e->getMessage());
    }

    return new JsonResponse(['success' => FALSE]);
  }

}
