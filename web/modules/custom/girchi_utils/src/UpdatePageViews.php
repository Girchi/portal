<?php

namespace Drupal\girchi_utils;

use Drupal\Core\Language\LanguageInterface;
use Google_Client;
use Google_Service_AnalyticsReporting;
use Google_Service_AnalyticsReporting_GetReportsRequest;
use Google_Service_AnalyticsReporting_GetReportsResponse;

class UpdatePageViews
{
  private static function _apiCall($url)
  {
    $reports = self::_getReports($url);
    $views = self::_getViews($reports);

    return $views;
  }

  /**
   * Initializes an Analytics Reporting API V4 service object.
   *
   * @return Google_Service_AnalyticsReporting An authorized Analytics Reporting API V4 service object.
   * @throws \Google_Exception
   */
  private static function _initializeAnalytics()
  {
    $KEY_FILE_LOCATION = __DIR__ . '/../service-account-credentials.json';

    // Create and configure a new client object.
    $client = new Google_Client();
    $client->setApplicationName("Girchi");
    $client->setAuthConfig($KEY_FILE_LOCATION);
    $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
    $analytics = new Google_Service_AnalyticsReporting($client);

    return $analytics;
  }

  /**
   * Get page view reports.
   *
   * @param $url Page URL.
   *
   * @return \Google_Service_AnalyticsReporting_GetReportsResponse Report Response
   *
   * @throws \Google_Exception
   */
  private static function _getReports($url)
  {
    $analytics = self::_initializeAnalytics();

    $viewId = \Drupal::config('om_site_settings.site_settings')->get('google_analytics_view_id');

    $query = [
      "viewId" => $viewId,
      "dateRanges" => [
        "startDate" => '2005-01-01',
        "endDate" => 'today',
      ],
      "metrics" => [
        "expression" => "ga:pageviews"
      ],
      "dimensions" => [
        "name" => "ga:pagepath"
      ],
      "dimensionFilterClauses" => [
        'filters' => [
          "dimension_name" => "ga:pagepath",
          "operator" => "PARTIAL", // valid operators can be found here: https://developers.google.com/analytics/devguides/reporting/core/v4/rest/v4/reports/batchGet#FilterLogicalOperator
          "expressions" => $url,
        ]
      ]
    ];

    $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
    $body->setReportRequests(array($query));

    // batchGet the results https://developers.google.com/analytics/devguides/reporting/core/v4/rest/v4/reports/batchGet
    $report = $analytics->reports->batchGet($body);

    return $report;
  }

  /**
   * Get page view count.
   *
   * @param Google_Service_AnalyticsReporting_GetReportsResponse $reports
   *    Google Analytics report object.
   *
   * @return int|null Page views or null.
   */
  private static function _getViews($reports)
  {
    $rows = $reports[0]->getData()->getRows();

    if ($rows) {
      $metrics = $rows[0]->getMetrics()[0]->values[0];

      if ($metrics) {
        return $metrics;
      }
    }

    return null;
  }

  public static function updateViews($nids, &$context)
  {
    $message = t('Updating Views...');
    $results = array();

    /** @var \Drupal\Core\Database\Connection $database */
    $database = \Drupal::service('database');

    foreach ($nids as $nid) {
      $path = '/node/' . (int)$nid;
      $langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();

      $url = \Drupal::service('path.alias_manager')->getAliasByPath($path, $langcode);

      $viewsCount = self::_apiCall('/ge' . $url);

      $connection = \Drupal::database();
      $prefix = $connection->tablePrefix();

      if ($viewsCount !== null) {
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
        } else {
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

  public static function updateViewsFinishedCallback($success, $results, $operations)
  {
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One node views has been updated.', '@count nodes views has been updated.'
      );
    } else {
      $message = t('Finished with an error.');
    }

    \Drupal::messenger()->addMessage($message);
  }

}