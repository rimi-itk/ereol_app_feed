<?php

namespace Drupal\ereol_feed\Helper;

use EntityFieldQuery;

/**
 * Paragraphs helper.
 */
class ParagraphHelper {
  const PARAGRAPH_AUDIO = 'audio_preview';
  const PARAGRAPH_AUTHOR_PORTRAIT = 'author_portrait';
  const PARAGRAPH_AUTHOR_QUOTE = 'author_quote';
  const PARAGRAPH_CAROUSEL = 'material_carousel';
  const PARAGRAPH_EDITOR = 'recommended_material';
  const PARAGRAPH_LINK = 'linkbox';
  const PARAGRAPH_PICKED_ARTICLE_CAROUSEL = 'picked_article_carousel';
  const PARAGRAPH_REVIEW = 'review';
  const PARAGRAPH_SPOTLIGHT_BOX = 'spotlight_box';
  const PARAGRAPH_THEME = 'article_carousel';
  const PARAGRAPH_VIDEO = 'video';

  /**
   * Get a list of paragraph ids on a list of nodes.
   */
  public function getParagraphIds(array $nids, $node_type = NULL, $recurse = TRUE) {
    $paragraphIds = [];
    $accumulator = function (\ParagraphsItemEntity $paragraph) use (&$paragraphIds) {
      $paragraphIds[] = $paragraph->item_id;
    };

    $entity_type = 'node';
    $query = new EntityFieldQuery();
    $query
      ->entityCondition('entity_type', $entity_type)
      ->propertyCondition('status', NODE_PUBLISHED)
      ->propertyCondition('nid', $nids ?: [0], 'IN');
    if (NULL !== $node_type) {
      $query->entityCondition('bundle', $node_type);
    }
    $result = $query->execute();

    if (isset($result[$entity_type])) {
      $nodes = node_load_multiple(array_keys($result[$entity_type]));
      foreach ($nodes as $node) {
        $paragraph_fields = $this->getParagraphFields($node);
        foreach ($paragraph_fields as $field_name => $field) {
          $paragraphs = $this->loadParagraphs($node, $field_name);
          $this->processParagraphs($paragraphs, TRUE, $accumulator);
        }
      }
    }

    return $paragraphIds;
  }

  /**
   * Process paragraphs, optionally recursively and depth first.
   *
   * @param \ParagraphsItemEntity[] $paragraphs
   *   The paragraphs.
   * @param bool $recurse
   *   Whether to recursively process paragraphs.
   * @param callable $accumulator
   *   Accumulator to accumulate results.
   */
  private function processParagraphs(array $paragraphs, $recurse, callable $accumulator) {
    foreach ($paragraphs as $paragraph) {
      $accumulator($paragraph);
      if ($recurse) {
        $paragraphFields = $this->getParagraphFields($paragraph);
        foreach ($paragraphFields as $field_name => $field) {
          $paragraphs = $this->loadParagraphs($paragraph, $field_name);
          $this->processParagraphs($paragraphs, $recurse, $accumulator);
        }
      }
    }
  }

  /**
   * Get paragraphs fields on an entity.
   *
   * @param object|\ParagraphsItemEntity $entity
   *   The entity.
   */
  private function getParagraphFields($entity) {
    if ($entity instanceof \ParagraphsItemEntity) {
      $entity_type = 'paragraphs_item';
      $bundle_name = $entity->bundle();
    }
    else {
      $entity_type = 'node';
      $bundle_name = $entity->type;
    }

    $paragraphFields = [];
    $fields = field_info_instances($entity_type, $bundle_name);
    foreach ($fields as $field_name => $info) {
      $field = field_info_field($field_name);
      if ($this->isParagraphsField($field)) {
        $paragraphFields[$field['field_name']] = $field;
      }
    }

    return $paragraphFields;
  }

  /**
   * Decide if a field is a paragraphs field.
   */
  private function isParagraphsField($field) {
    if (is_string($field)) {
      $field = field_info_field($field);
    }

    return 'paragraphs' === $field['type'];
  }

  /**
   * Load paragraphs from a paragraphs field on an entity.
   */
  private function loadParagraphs($entity, $field_name) {
    if ($this->isParagraphsField($field_name) && isset($entity->{$field_name}[LANGUAGE_NONE])) {
      $values = $entity->{$field_name}[LANGUAGE_NONE];

      return paragraphs_item_load_multiple(array_column($values, 'value'));
    }

    return [];
  }

  /**
   * Get data for a list of paragraphs.
   */
  public function getParagraphsData($type, array $paragraphIds) {
    $entity_type = 'paragraphs_item';
    $query = new EntityFieldQuery();
    $query
      ->entityCondition('entity_type', $entity_type)
      ->entityCondition('bundle', $type)
      ->propertyCondition('item_id', $paragraphIds ?: [0], 'IN');
    $result = $query->execute();

    if (isset($result[$entity_type])) {
      $paragraphs = paragraphs_item_load_multiple(array_keys($result[$entity_type]));
      $data = array_map([$this, 'getParagraphData'], $paragraphs);
    }

    // Flatten the array.
    return call_user_func_array('array_merge', $data);
  }

  /**
   * Get data for a single paragraph.
   */
  public function getParagraphData(\ParagraphsItemEntity $paragraph) {
    $bundle = $paragraph->bundle();

    switch ($bundle) {
      case self::PARAGRAPH_AUDIO:
        return $this->getAudio($paragraph);

      case self::PARAGRAPH_AUTHOR_PORTRAIT:
        return $this->getAuthorPortrait();

      case self::PARAGRAPH_AUTHOR_QUOTE:
        return $this->getAuthorQuote();

      case self::PARAGRAPH_CAROUSEL:
        return $this->getCarousel($paragraph);

      case self::PARAGRAPH_EDITOR:
        return $this->getEditor($paragraph);

      case self::PARAGRAPH_LINK:
        return $this->getLink($paragraph);

      case self::PARAGRAPH_REVIEW:
        return $this->getReview($paragraph);

      case self::PARAGRAPH_SPOTLIGHT_BOX:
        return $this->getSpotlightBox();

      case self::PARAGRAPH_THEME:
        return $this->getTheme($paragraph);

      case self::PARAGRAPH_VIDEO:
        return $this->getVideo($paragraph);
    }

    return NULL;
  }

  /**
   * Get carousel data.
   */
  private function getCarousel(\ParagraphsItemEntity $paragraph) {
    $data = [];
    if (isset($paragraph->field_carousel[LANGUAGE_NONE])) {
      foreach ($paragraph->field_carousel[LANGUAGE_NONE] as $index => $value) {
        $data[] = [
          'guid' => $this->getGuid($paragraph, $index),
          'type' => $this->getType($paragraph),
          'title' => $value['title'],
          'view' => $this->getView($paragraph),
          'query' => $value['search'],
        ];
      }
    }

    return $data;
  }

  /**
   * Get theme data.
   */
  private function getTheme(\ParagraphsItemEntity $paragraph) {
    return [__METHOD__];
  }

  /**
   * Get link data.
   */
  private function getLink(\ParagraphsItemEntity $paragraph) {
    return [__METHOD__];
  }

  /**
   * Get review data.
   */
  private function getReview(\ParagraphsItemEntity $paragraph) {
    return [__METHOD__];
  }

  /**
   * Get editor data.
   */
  private function getEditor(\ParagraphsItemEntity $paragraph) {
    return [__METHOD__];
  }

  /**
   * Get video data.
   */
  private function getVideo(\ParagraphsItemEntity $paragraph) {
    return [__METHOD__];
  }

  /**
   * Get audio data.
   */
  private function getAudio(\ParagraphsItemEntity $paragraph) {
    return [__METHOD__];
  }

  /**
   * Get guid (generally unique id) for a paragraph.
   *
   * The guid is NOT guaranteed bo be globally unique.
   */
  private function getGuid(\ParagraphsItemEntity $paragraph, $delta = NULL) {
    $guid = $paragraph->identifier();
    if (NULL !== $delta) {
      $guid .= '-' . $delta;
    }

    return $guid;
  }

  /**
   * Get data type for a paragraph.
   */
  private function getType(\ParagraphsItemEntity $paragraph) {
    $bundle = $paragraph->bundle();

    switch ($bundle) {
      case self::PARAGRAPH_PICKED_ARTICLE_CAROUSEL:
        return 'picked_article_carousel';

      case self::PARAGRAPH_AUTHOR_PORTRAIT:
        return 'author_portrait';

      case self::PARAGRAPH_AUTHOR_QUOTE:
        return 'author_quote';

      case self::PARAGRAPH_CAROUSEL:
        return 'carousel';

      case self::PARAGRAPH_EDITOR:
        return 'editor';

      case self::PARAGRAPH_LINK:
        return 'link';

      case self::PARAGRAPH_REVIEW:
        return 'review';

      case self::PARAGRAPH_SPOTLIGHT_BOX:
        return 'spotlight_box';

      case self::PARAGRAPH_THEME:
        return 'theme';

      case self::PARAGRAPH_VIDEO:
        return 'video';
    }
  }

  /**
   * Get view for a paragraph.
   */
  private function getView(\ParagraphsItemEntity $paragraph) {
    // 'dotted' or 'scroll'.
    return 'scroll';
  }

}
