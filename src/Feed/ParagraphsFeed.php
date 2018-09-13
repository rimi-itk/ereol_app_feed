<?php

namespace Drupal\ereol_app_feeds\Feed;

use Drupal\ereol_app_feeds\Helper\ParagraphHelper;

/**
 * Paragraphs feed.
 */
class ParagraphsFeed extends AbstractFeed {

  /**
   * Get feed data.
   */
  public function getData($nids, $type) {
    $helper = new ParagraphHelper();
    $type = $helper->getParagraphType($type);
    $ids = $helper->getParagraphIds($nids);
    return $helper->getParagraphsData($type, $ids);
  }

}