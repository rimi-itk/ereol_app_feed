<?php

namespace Drupal\ereol_app_feed\Controller;

use Drupal\ereol_app_feed\Helper\ParagraphHelper;

/**
 * Paragraphs controller.
 */
class ParagraphsController extends AbstractController {

  /**
   * Render paragraphs data.
   */
  public function index($type) {
    $nids = $this->getQueryParameter('nids');

    $helper = new ParagraphHelper();
    $ids = $helper->getParagraphIds($nids);
    $data = $helper->getParagraphsData($type, $ids);

    drupal_json_output($data);
  }

}
