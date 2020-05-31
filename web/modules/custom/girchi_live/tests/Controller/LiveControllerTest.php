<?php

namespace Drupal\girchi_live\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the girchi_live module.
 */
class LiveControllerTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => "girchi_live LiveController's controller functionality",
      'description' => 'Test Unit for module girchi_live and controller LiveController.',
      'group' => 'Other',
    ];
  }

  /**
   * Tests girchi_live functionality.
   */
  public function testLiveController() {
    // Check that the basic functions of module girchi_live.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via Drupal Console.');
  }

}
