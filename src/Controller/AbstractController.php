<?php

namespace Drupal\ereol_feed\Controller;

/**
 * Abstract controller.
 */
class AbstractController {

  /**
   * Get a query parameter.
   */
  protected function getQueryParameter($name) {
    $query_parameters = drupal_get_query_parameters();
    $value = isset($query_parameters[$name]) ? $query_parameters[$name] : NULL;

    // Normalize "nids" to be an array of integers.
    if ('nids' === $name) {
      if (empty($value)) {
        $value = [];
      }
      elseif (!is_array($value)) {
        $value = preg_split('/\s*,\s*/', $value, -1, PREG_SPLIT_NO_EMPTY);
      }
      $value = array_unique(array_map('intval', $value));
    }

    return $value;
  }

}
