<?php

namespace Drupal\girchi_reports\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the girchi_reports module.
 */
class UsersReportControllerTest extends WebTestBase {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => "girchi_reports UsersReportController's controller functionality",
      'description' => 'Test Unit for module girchi_reports and controller UsersReportController.',
      'group' => 'Other',
    ];
  }

  /**
   * Tests girchi_reports functionality.
   */
  public function testUsersReportController() {
    // Check that the basic functions of module girchi_reports.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via Drupal Console.');
  }

}
