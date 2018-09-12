<?php

namespace Drupal\ereol_app_feed\Controller;

use Drupal\ereol_app_feed\Helper\FrontPageHelper;

/**
 * Frontpage controller.
 */
class FrontpageController extends AbstractController {

  /**
   * Render frontpage data.
   */
  public function index() {
    $helper = new FrontPageHelper();
    $data = $helper->getFrontpageData();

    drupal_json_output($data);
  }

}
