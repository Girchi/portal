<?php

namespace Drupal\girchi_live\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class LiveController.
 */
class LiveController extends ControllerBase {

  /**
   * Livestrem url for player.
   *
   * @var string
   */
  private $livestreamUrl = 'https://tv.myvideo.ge/embed.html?chan=girchitv';

  /**
   * Hello.
   *
   * @return string
   *   Return Hello string.
   */
  public function index() {
    return [
      '#theme' => 'girchi_live_page',
      '#live_url' => $this->livestreamUrl,
      '#attached' => [
        'library' => [
          'girchi_live/live',
        ],
      ],
    ];
  }

}
