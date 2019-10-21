<?php

namespace Drupal\girchi_users;

/**
 * Class GEDHelperService.
 */
class GEDHelperService {

  /**
   * Get short number characters.
   */
  public static function getShortNumberCharacters() {
    return [
      'K',
      'M',
      'B',
      'T',
    ];
  }

  /**
   * Taken from here: https://git.drupalcode.org/project/short_scale_formatter/blob/7.x-1.x/short_scale_formatter.module#L161.
   *
   * ShortScaleFormatNumber().
   *
   * This function does the work of formatting the number.
   * This code is based off
   * of code from the following URL but reworked slightly for what we want. No
   * rounding up, 1 decimal place.
   * http://stackoverflow.com/questions/4753251/how-to-go-about-formatting-1200-to-1-2k-in-java
   */
  public static function shortScaleFormatNumber($n, $iteration = 0) {
    $characters = self::getShortNumberCharacters();
    if ($n < 1000 && $iteration == 0) {
      return [
        '#formatted' => $n,
        '#character' => NULL,
      ];
    }

    $d = ($n / 100) / 10.0;

    if ($d < 1000) {
      $return = [
        '#formatted' => (floor($d * 10) / 10) . $characters[$iteration],
        '#character' => strtolower($characters[$iteration]),
      ];
    }
    else {
      $return = self::shortScaleFormatNumber($d, $iteration + 1);
    }

    return $return;
  }

  /**
   * Get formatted ged.
   */
  public static function getFormattedGed($ged) {
    return self::shortScaleFormatNumber($ged)['#formatted'];
  }

}
