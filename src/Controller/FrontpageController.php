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
    $nids = $this->getQueryParameter('nids');

    $helper = new FrontPageHelper();
    $ids = $helper->getParagraphIds($nids);
    $data = $helper->getFrontpageData($ids);

    drupal_json_output($data);
  }

}
