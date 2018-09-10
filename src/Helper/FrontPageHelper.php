<?php

namespace Drupal\ereol_feed\Helper;

/**
 * Namespace.
 */
class FrontPageHelper {
  const NODE_TYPE = 'inspiration';

  /**
   * Map [name => paragraph type].
   */
  private static $paragraphTypes = [
    'carousels' => 'material_carousel',
    'themes' => 'article_carousel',
    'links' => 'linkbox',
    'reviews' => 'review',
    'editor' => 'recommended_material',
    'videos' => 'video',
    'audio' => 'audio_preview',

        // // Paragraph types not currently used.
        // null => 'author_quote',
        // null => 'picked_article_carousel',
        // null => 'spotlight_box',
        // null => 'author_portrait',.
        // // Excluded paragraph types.
        // null => 'newsletter_signup',.
  ];

  /**
   * Map [paragraph type => get data function].
   */
  private static $getDataFunctions = [
    'material_carousel' => 'getCarousel',
    'article_carousel' => 'getTheme',
    'linkbox' => 'getLink',
    'review' => 'getLink',
    'recommended_material' => 'getEditor',
    'video' => 'getVideo',
    'audio_preview' => 'getAudio',

        // Paragraph types not currently used.
    'author_quote' => NULL,
    'picked_article_carousel' => NULL,
    'spotlight_box' => NULL,
    'author_portrait' => NULL,

        // Excluded paragraph types.
    'newsletter_signup' => NULL,
  ];

  /**
   * Get frontpage data.
   */
  public function getData() {
    $query_parameters = drupal_get_query_parameters();

    $nids = isset($query_parameters['nids']) ? $query_parameters['nids'] : [];
    $type = 'inspiration';
    $nodes = node_load_multiple($nids, ['type' => $type]);

    $data = [];
    foreach (self::$paragraphTypes as $name => $paragraph_type) {
      $data[$name] = $this->getParagraphsData($paragraph_type, $nodes);
    }

    return $data;
  }

  /**
   *
   */
  private function getParagraphsData($paragraph_type, array $nodes) {
    $paragraphs = $this->getParagraphs($nodes, function (\ParagraphsItemEntity $paragraph) use ($paragraph_type) {
      return $paragraph->bundle() === $paragraph_type;
    });

    return call_user_func_array('array_merge', array_map($this->getParagraphDataGenerator($paragraph_type), $paragraphs));
  }

  /**
   *
   */
  private function getParagraphDataGenerator($paragraph_type) {
    if (!isset(self::$getDataFunctions[$paragraph_type])) {
      throw new \Exception('Unknown paragraph type: ' . $paragraph_type);
    }
    $function = self::$getDataFunctions[$paragraph_type];
    if (!method_exists($this, $function)) {
      throw new \Exception('No such function: ' . $function);
    }

    return [$this, $function];
  }

  /**
   *
   */
  private function getCarousel(\ParagraphsItemEntity $paragraph) {
    $data = [];
    if (isset($paragraph->field_carousel[LANGUAGE_NONE])) {
      foreach ($paragraph->field_carousel[LANGUAGE_NONE] as $value) {
        $data[] = [
          'guid' => $paragraph->identifier(),
          'type' => $paragraph->bundle(),
          'title' => $value['title'],
        // 'dotted' eller 'scroll'.
          'view' => NULL,
          'query' => $value['search'],
        ];
      }
    }

    return $data;
  }

  /**
   *
   */
  private function getTheme(\ParagraphsItemEntity $paragraph) {
    return __METHOD__;
  }

  /**
   *
   */
  private function getLink(\ParagraphsItemEntity $paragraph) {
    return __METHOD__;
  }

  /**
   *
   */
  private function getReview(\ParagraphsItemEntity $paragraph) {
    return __METHOD__;
  }

  /**
   *
   */
  private function getEditor(\ParagraphsItemEntity $paragraph) {
    return __METHOD__;
  }

  /**
   *
   */
  private function getVideo(\ParagraphsItemEntity $paragraph) {
    return __METHOD__;
  }

  /**
   *
   */
  private function getAudio(\ParagraphsItemEntity $paragraph) {
    return __METHOD__;
  }

  /**
   *
   */
  private function getParagraphs(array $nodes, $callback) {
    $paragraphs = [];
    foreach ($nodes as $node) {
      if (self::NODE_TYPE === $node->type) {
        $paragraph_items = $node->field_inspiration_paragraphs[LANGUAGE_NONE];
        foreach ($paragraph_items as $item) {
          $paragraph = paragraphs_item_revision_load($item['revision_id']);
          if ($callback($paragraph)) {
            $paragraphs[] = $paragraph;
          }
        }
      }
    }

    return $paragraphs;
  }

}
