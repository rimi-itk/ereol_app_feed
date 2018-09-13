<?php

namespace Drupal\ereol_app_feeds\Controller;

use Drupal\ereol_app_feeds\Helper\FrontPageHelper;
use Drupal\ereol_app_feeds\Helper\ParagraphHelper;

/**
 * Paragraphs controller.
 */
class ParagraphsController extends AbstractController {

  /**
   * Render paragraphs data.
   */
  public function index($type) {
    $nids = $this->getQueryParameter('nids', FrontPageHelper::getFrontPageIds());

    $helper = new ParagraphHelper();
    $type = $helper->getParagraphType($type);
    $ids = $helper->getParagraphIds($nids);
    $data = $helper->getParagraphsData($type, $ids);

    drupal_json_output($data);
  }

}
