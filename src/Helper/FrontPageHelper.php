<?php

namespace Drupal\ereol_app_feed\Helper;

/**
 * Frontpage helper.
 */
class FrontPageHelper extends ParagraphHelper {
  const NODE_TYPE_INSPIRATION = 'inspiration';

  /**
   * {@inheritdoc}
   *
   * Load only paragraphs on "inspiration" pages.
   */
  public function getParagraphIds(array $nids, $node_type = NULL, $recurse = TRUE) {
    return parent::getParagraphIds($nids, self::NODE_TYPE_INSPIRATION, $recurse);
  }

  /**
   * Get frontpage data.
   */
  public function getFrontpageData(array $paragraphIds) {
    $data = [
      'carousel' => $this->getParagraphsData(ParagraphHelper::PARAGRAPH_CAROUSEL, $paragraphIds),
      'theme' => $this->getParagraphsData(ParagraphHelper::PARAGRAPH_THEME, $paragraphIds),
      'link' => $this->getParagraphsData(ParagraphHelper::PARAGRAPH_LINK, $paragraphIds),
      'review' => $this->getParagraphsData(ParagraphHelper::PARAGRAPH_REVIEW, $paragraphIds),
      'editor' => $this->getParagraphsData(ParagraphHelper::PARAGRAPH_EDITOR, $paragraphIds),
      'video' => $this->getParagraphsData(ParagraphHelper::PARAGRAPH_VIDEO, $paragraphIds),
      'audio' => $this->getParagraphsData(ParagraphHelper::PARAGRAPH_AUDIO, $paragraphIds),
    ];

    return $data;
  }

}
