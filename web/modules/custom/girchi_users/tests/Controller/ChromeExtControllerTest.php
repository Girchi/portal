<?php

namespace Drupal\girchi_users\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the girchi_users module.
 */
class ChromeExtControllerTest extends WebTestBase {

  /**
   * ArrayObject definition.
   *
   * @var \ArrayObject
   */
  protected $containerNamespaces;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => "girchi_users ChromeExtController's controller functionality",
      'description' => 'Test Unit for module girchi_users and controller ChromeExtController.',
      'group' => 'Other',
    ];
  }

  /**
   * Tests girchi_users functionality.
   */
  public function testChromeExtController() {
    // Check that the basic functions of module girchi_users.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via Drupal Console.');
  }

}
