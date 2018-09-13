<?php

namespace Drupal\ereol_app_feeds\Helper;

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
  public static function getFrontPageIds() {
    return variable_get('ereol_app_feeds_frontpage_ids', []);
  }

  /**
   * Get frontpage data.
   */
  public function getFrontpageData() {
    $frontPageIds = self::getFrontPageIds();
    $paragraphIds = $this->getParagraphIds($frontPageIds, self::NODE_TYPE_INSPIRATION, TRUE);

    $data = [
      'carousels' => $this->getCarousels($paragraphIds),
      'themes' => $this->getThemes($paragraphIds),
      'links' => $this->getLinks($paragraphIds),
      'reviews' => $this->getReviews($paragraphIds),
      'editor' => $this->getEditors($paragraphIds),
      'videos' => $this->getVideos($paragraphIds),
      'audio' => $this->getAudios($paragraphIds),
    ];

    return $data;
  }

  /**
   * Get carousels.
   */
  private function getCarousels(array $paragraphIds) {
    return $this->getParagraphsData(ParagraphHelper::PARAGRAPH_ALIAS_CAROUSEL, $paragraphIds);
  }

  /**
   * Get themes.
   */
  private function getThemes(array $paragraphIds) {
    return $this->getParagraphsData(ParagraphHelper::PARAGRAPH_ALIAS_THEME_LIST, $paragraphIds);
  }

  /**
   * Get links.
   */
  private function getLinks(array $paragraphIds) {
    return $this->getParagraphsData(ParagraphHelper::PARAGRAPH_ALIAS_LINK, $paragraphIds);
  }

  /**
   * Get reviews.
   */
  private function getReviews(array $paragraphIds) {
    return $this->getParagraphsData(ParagraphHelper::PARAGRAPH_REVIEW, $paragraphIds);
  }

  /**
   * Get editors.
   */
  protected function getEditors(array $paragraphIds) {
    $data = [];

    $paragraphs = $this->getParagraphs(ParagraphHelper::PARAGRAPH_SPOTLIGHT_BOX, $paragraphIds);

    foreach ($paragraphs as $paragraph) {
      $data[] = $this->getEditor($paragraph);
    }

    return $data;
  }

  /**
   * Get videos.
   */
  private function getVideos(array $paragraphIds) {
    // Wrap all videos in a fake list element.
    $list = [];
    $paragraphs = $this->getParagraphs(ParagraphHelper::PARAGRAPH_SPOTLIGHT_BOX, $paragraphIds);

    foreach ($paragraphs as $paragraph) {
      $item = $this->getVideoList($paragraph);
      if (!empty($item)) {
        $list[] = $item;
      }
    }

    return [
      'guid' => ParagraphHelper::VALUE_NONE,
      'type' => 'video_list',
      'view' => ParagraphHelper::VIEW_DOTTED,
      'list' => $list,
    ];
  }

  /**
   * Get audio.
   */
  private function getAudios(array $paragraphIds) {
    // Wrap all videos audio samples in a fake list element.
    $list = $this->getParagraphsData(ParagraphHelper::PARAGRAPH_ALIAS_AUDIO, $paragraphIds);

    return [
      'guid' => ParagraphHelper::VALUE_NONE,
      'type' => 'audio_sample_list',
      'view' => ParagraphHelper::VIEW_DOTTED,
      'list' => $list,
    ];
  }

}
