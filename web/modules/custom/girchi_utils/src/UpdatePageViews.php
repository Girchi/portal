<?php

namespace Drupal\girchi_utils;

use Drupal\Core\Language\LanguageInterface;
use Google_Client;
use Google_Service_AnalyticsReporting;
use Google_Service_AnalyticsReportinggetReportsRequest;

/**
 * Class UpdatePageViews.
 */
class UpdatePageViews {

  /**
   * Static function for api call.
   */
  private static function apiCall($url) {
    $reports = self::getReports($url);
    $views = self::getViews($reports);
    return $views;
  }

  /**
   * Initializes an Analytics Reporting API V4 service object.
   *
   * @return \Google_Service_AnalyticsReporting
   *   An authorized Analytics Reporting API V4 service object.
   *
   * @throws \Google_Exception
   */
  private static function initializeAnalytics() {
    $key_file_location = __DIR__ . '/../service-account-credentials.json';
    // Create and configure a new client object.
    $client = new Google_Client();
    $client->setApplicationName("Girchi");
    $client->setAuthConfig($key_file_location);
    $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
    $analytics = new Google_Service_AnalyticsReporting($client);
    return $analytics;
  }

  /**
   * Get page view reports.
   *
   * @param string $url
   *   Page URL.
   *
   * @return \Google_Service_AnalyticsReportinggetReportsResponse
   *   Report Response
   *
   * @throws \Google_Exception
   */
  private static function getReports($url) {
    $analytics = self::initializeAnalytics();
    $viewId = \Drupal::config('om_site_settings.site_settings')->get('google_analytics_view_id');
    $query = [
      "viewId" => $viewId,
      "dateRanges" => [
        "startDate" => '2005-01-01',
        "endDate" => 'today',
      ],
      "metrics" => [
        "expression" => "ga:pageviews",
      ],
      "dimensions" => [
        "name" => "ga:pagepath",
      ],
      "dimensionFilterClauses" => [
        'filters' => [
          "dimension_name" => "ga:pagepath",
      // Valid operators can be found here: https://developers.google.com/analytics/devguides/reporting/core/v4/rest/v4/reports/batchGet#FilterLogicalOperator
          "operator" => "PARTIAL",
          "expressions" => $url,
        ],
      ],
    ];
    $body = new Google_Service_AnalyticsReportinggetReportsRequest();
    $body->setReportRequests([$query]);
    // batchGet the results https://developers.google.com/analytics/devguides/reporting/core/v4/rest/v4/reports/batchGet
    $report = $analytics->reports->batchGet($body);
    return $report;
  }

  /**
   * Get page view count.
   *
   * @param \Google_Service_AnalyticsReportinggetReportsResponse $reports
   *   Google Analytics report object.
   *
   * @return int|null
   *   Page views or null.
   */
  private static function getViews(\Google_Service_AnalyticsReportinggetReportsResponse $reports) {
    $rows = $reports[0]->getData()->getRows();
    if ($rows) {
      $metrics = $rows[0]->getMetrics()[0]->values[0];
      if ($metrics) {
        return $metrics;
      }
    }
    return NULL;
  }

  /**
   * Update views.
   */
  public static function updateViews($nids, &$context) {
    $message = t('Updating Views...');
    $results = [];
    /** @var \Drupal\Core\Database\Connection $database */
    $database = \Drupal::service('database');
    foreach ($nids as $nid) {
      $path = '/node/' . (int) $nid;
      $langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
      $url = \Drupal::service('path.alias_manager')->getAliasByPath($path, $langcode);
      $viewsCount = self::apiCall('/ge' . $url);
      $connection = \Drupal::database();
      $prefix = $connection->tablePrefix();
      if ($viewsCount !== NULL) {
        $results[$url] = $viewsCount;
        $currentState = $database->query(
              "SELECT * FROM `{$prefix}node_counter` WHERE `nid` = :nid;",
              [
                ':nid' => $nid,
              ]
          );
        $currentViews = $currentState->fetchAll();
        if (empty($currentViews)) {
          $database->query(
          "INSERT INTO `{$prefix}node_counter` (`nid`, `totalcount`, `daycount`, `timestamp`) VALUES (:nid, :views_count, :views_count, :curent_time);",
          [
            ':views_count' => $viewsCount,
            ':nid' => $nid,
            ':curent_time' => time(),
          ]
            );
        }
        else {
          $database->query(
          "UPDATE `{$prefix}node_counter` SET `totalcount` = :views_count WHERE `nid` = :nid;",
          [
            ':views_count' => $viewsCount,
            ':nid' => $nid,
          ]
              );
        }
      }
    }
    $context['message'] = $message;
    $context['results'] = $results;
  }

  /**
   * Finished callback function.
   */
  public static function updateViewsFinishedCallback($success, $results, $operations) {
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
            count($results),
            'One node views has been updated.', '@count nodes views has been updated.'
        );
    }
    else {
      $message = t('Finished with an error.');
    }
    \Drupal::messenger()->addMessage($message);
  }

}
