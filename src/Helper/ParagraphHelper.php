<?php

namespace Drupal\ereol_app_feed\Helper;

use EntityFieldQuery;

/**
 * Paragraphs helper.
 */
class ParagraphHelper {
  // Nyeste nyheds karrusel.
  const PARAGRAPH_ARTICLE_CAROUSEL = 'article_carousel';
  // Lydprøve.
  const PARAGRAPH_AUDIO_PREVIEW = 'audio_preview';
  // Forfatterportræt.
  const PARAGRAPH_AUTHOR_PORTRAIT = 'author_portrait';
  // Forfattercitat.
  const PARAGRAPH_AUTHOR_QUOTE = 'author_quote';
  // Linkbox.
  const PARAGRAPH_LINKBOX = 'linkbox';
  // Materiale karrusel.
  const PARAGRAPH_MATERIAL_CAROUSEL = 'material_carousel';
  // Nyhedsbrevstilmelding.
  const PARAGRAPH_NEWSLETTER_SIGNUP = 'newsletter_signup';
  // Nyheds karrusel.
  const PARAGRAPH_PICKED_ARTICLE_CAROUSEL = 'picked_article_carousel';
  // Redaktøren anbefaler.
  const PARAGRAPH_RECOMMENDED_MATERIAL = 'recommended_material';
  // Litteratursiden anmeldelse.
  const PARAGRAPH_REVIEW = 'review';
  // Formidlingsboks.
  const PARAGRAPH_SPOTLIGHT_BOX = 'spotlight_box';
  // Video.
  const PARAGRAPH_VIDEO = 'video';

  // Paragraph aliases.
  const PARAGRAPH_ALIAS_AUDIO = self::PARAGRAPH_AUDIO_PREVIEW;
  const PARAGRAPH_ALIAS_CAROUSEL = self::PARAGRAPH_MATERIAL_CAROUSEL;
  const PARAGRAPH_ALIAS_EDITOR = self::PARAGRAPH_RECOMMENDED_MATERIAL;
  const PARAGRAPH_ALIAS_LINK = self::PARAGRAPH_LINKBOX;
  const PARAGRAPH_ALIAS_THEME = self::PARAGRAPH_ARTICLE_CAROUSEL;
  const PARAGRAPH_ALIAS_THEME_LIST = self::PARAGRAPH_PICKED_ARTICLE_CAROUSEL;

  const VIEW_DOTTED = 'dotted';
  const VIEW_SCROLL = 'scroll';

  const VALUE_NONE = '';

  /**
   * The node helper.
   *
   * @var \Drupal\ereol_app_feed\Helper\NodeHelper
   */
  protected $nodeHelper;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->nodeHelper = new NodeHelper();
  }

  /**
   * Get a list of paragraph ids on a list of nodes.
   */
  public function getParagraphIds(array $nids, $node_type = NULL, $recurse = TRUE) {
    $paragraphIds = [];
    $accumulator = function (\ParagraphsItemEntity $paragraph) use (&$paragraphIds) {
      $paragraphIds[] = $paragraph->item_id;
    };

    $entity_type = NodeHelper::ENTITY_TYPE_NODE;
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
   * Get paragraphs fields on an entity (node or paragraph).
   *
   * @param object|\ParagraphsItemEntity $entity
   *   The entity.
   */
  protected function getParagraphFields($entity) {
    if ($entity instanceof \ParagraphsItemEntity) {
      $entity_type = NodeHelper::ENTITY_TYPE_PARAGRAPH;
      $bundle_name = $entity->bundle();
    }
    else {
      $entity_type = NodeHelper::ENTITY_TYPE_NODE;
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
  public function getParagraphs($type, array $paragraphIds = NULL, callable $filter = NULL) {
    $entity_type = NodeHelper::ENTITY_TYPE_PARAGRAPH;
    $query = new EntityFieldQuery();
    $query
      ->entityCondition('entity_type', $entity_type)
      ->entityCondition('bundle', $type)
      ->entityCondition('entity_id', $paragraphIds ?: [0], 'IN');
    $result = $query->execute();

    if (isset($result[$entity_type])) {
      $paragraphs = paragraphs_item_load_multiple(array_keys($result[$entity_type]));
      if (NULL !== $filter) {
        $paragraphs = array_filter($paragraphs, $filter);
      }

      return $paragraphs;
    }

    return NULL;
  }

  /**
   * Get data for a list of paragraphs.
   */
  public function getParagraphsData($type, array $paragraphIds) {
    $paragraphs = $this->getParagraphs($type, $paragraphIds);
    if (NULL !== $paragraphs) {
      $data = array_values(array_map([$this, 'getParagraphData'], $paragraphs));

      // Make sure that all items are numeric arrays.
      foreach ($data as &$datum) {
        if ($this->isAssoc($datum)) {
          $datum = [$datum];
        }
      }

      // Flatten the data.
      return array_merge(...$data);
    }

    return NULL;
  }

  /**
   * Get data for a single paragraph.
   *
   * @return array
   *   The paragraph data.
   */
  public function getParagraphData(\ParagraphsItemEntity $paragraph) {
    $bundle = $paragraph->bundle();

    switch ($bundle) {
      case self::PARAGRAPH_ALIAS_AUDIO:
        return $this->getAudio($paragraph);

      case self::PARAGRAPH_AUTHOR_PORTRAIT:
        return $this->getAuthorPortrait();

      case self::PARAGRAPH_AUTHOR_QUOTE:
        return $this->getAuthorQuote();

      case self::PARAGRAPH_ALIAS_CAROUSEL:
        return $this->getCarousel($paragraph);

      case self::PARAGRAPH_ALIAS_EDITOR:
        return $this->getEditor($paragraph);

      case self::PARAGRAPH_ALIAS_LINK:
        return $this->getLink($paragraph);

      case self::PARAGRAPH_REVIEW:
        return $this->getReview($paragraph);

      case self::PARAGRAPH_SPOTLIGHT_BOX:
        return $this->getSpotlightBox();

      case self::PARAGRAPH_ALIAS_THEME:
        return $this->getTheme($paragraph);

      case self::PARAGRAPH_ALIAS_THEME_LIST:
        return $this->getThemeList($paragraph);

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
    return [
      'guid' => $this->getGuid($paragraph),
      'type' => $this->getType($paragraph),
      'view' => $this->getView($paragraph),
      'list' => $this->getThemeList($paragraph),
    ];
  }

  /**
   * Get theme list.
   */
  private function getThemeList(\ParagraphsItemEntity $paragraph) {
    $items = $this->nodeHelper->loadReferences($paragraph, 'field_picked_articles');

    return [
      'guid' => $this->getGuid($paragraph),
      'type' => $this->getType($paragraph),
      'list' => array_values(array_map([$this, 'getThemeData'], $items)),
    ];
  }

  /**
   * Get theme data.
   */
  private function getThemeData($node) {
    return [
      'guid' => $node->nid,
      'type' => 'theme',
      'title' => $node->title,
      'view' => $this->nodeHelper->getFieldValue($node, 'field_image_teaser', 'value') ? 'image' : 'covers',
      'image' => $this->nodeHelper->getImage($node->field_ding_news_list_image),
      'body' => $this->nodeHelper->getBody($node),
      'identifiers' => $this->nodeHelper->getTingIdentifiers($node, 'field_ding_news_materials'),
    ];
  }

  /**
   * Get link data.
   */
  private function getLink(\ParagraphsItemEntity $paragraph) {
    return [
      'guid' => $this->getGuid($paragraph),
      'type' => $this->getType($paragraph),
      'title' => $this->nodeHelper->getFieldValue($paragraph, 'field_link', 'title'),
      'url' => $this->nodeHelper->getFieldValue($paragraph, 'field_link', 'url'),
      'color' => ltrim('#', $this->nodeHelper->getFieldValue($paragraph, 'field_link_color', 'rgb')),
      'subtitle' => self::VALUE_NONE,
      'button_text' => self::VALUE_NONE,
    ];
  }

  /**
   * Get review data.
   */
  private function getReview(\ParagraphsItemEntity $paragraph) {
    return [
      'guid' => $this->getGuid($paragraph),
      'type' => $this->getType($paragraph),
      'view' => $this->getView($paragraph),
      'list' => $this->getReviewList($paragraph),
    ];
  }

  /**
   * Get review list.
   */
  private function getReviewList(\ParagraphsItemEntity $paragraph) {
    // @TODO: Get some random stuff from somewhere
    return [];
  }

  /**
   * Get editor data.
   */
  protected function getEditor(\ParagraphsItemEntity $paragraph) {
    return [
      'guid' => $this->getGuid($paragraph),
      'type' => $this->getType($paragraph),
      'view' => $this->getView($paragraph),
      'list' => $this->getEditorList($paragraph),
    ];
  }

  /**
   * Get editor list.
   */
  private function getEditorList(\ParagraphsItemEntity $paragraph) {
    $subParagraphIds = $this->nodeHelper->getFieldValue($paragraph, 'field_spotlight_row_3', 'value', TRUE);
    $subParagraphs = $this->getParagraphs(ParagraphHelper::PARAGRAPH_RECOMMENDED_MATERIAL, $subParagraphIds);
    $list = array_values(array_map(function (\ParagraphsItemEntity $subParagraph) {
      return [
        'guid' => $this->getGuid($subParagraph),
        'type' => 'editor_recommends',
        'title' => $this->nodeHelper->getTextFieldValue($subParagraph, 'field_rec_title'),
        'identifier' => $this->nodeHelper->getTingIdentifierFromUrl($this->nodeHelper->getTextFieldValue($subParagraph, 'field_rec_material')),
      ];
    }, $subParagraphs));

    return $list;
  }

  /**
   * Get editor data.
   */
  protected function getVideo(\ParagraphsItemEntity $paragraph) {
    // Wrap all videos in a fake list element.
    return [
      'guid' => $this->getGuid($paragraph),
      'type' => $this->getType($paragraph),
      'view' => $this->getView($paragraph),
      'list' => $this->getVideoList($paragraph),
    ];
  }

  /**
   * Get video data.
   *
   * @return array
   *   The data.
   */
  protected function getVideoList(\ParagraphsItemEntity $paragraph) {
    $subParagraphIds = $this->nodeHelper->getFieldValue($paragraph, 'field_spotlight_primary', 'value', TRUE);
    $subParagraphs = $this->getParagraphs(ParagraphHelper::PARAGRAPH_VIDEO, $subParagraphIds);
    $list = array_values(array_map(function (\ParagraphsItemEntity $subParagraph) {
      $video = $this->nodeHelper->loadReferences($subParagraph, 'field_video_node', FALSE);
      return [
        'guid' => $this->getGuid($subParagraph),
        'type' => $this->getType($subParagraph),
        'title' => $video->title,
        'image' => self::VALUE_NONE,
        'source' => self::VALUE_NONE,
        'url' => $this->nodeHelper->getUrl($this->nodeHelper->getFieldValue($video, 'field_video', 'uri')),
      ];
    }, $subParagraphs));

    return $list;
  }

  /**
   * Get audio data.
   */
  private function getAudio(\ParagraphsItemEntity $paragraph) {
    $identifier = $this->nodeHelper->getTingIdentifierFromUrl(
      $this->nodeHelper->getTextFieldValue($paragraph, 'field_preview_material')
    );
    if (NULL !== $identifier) {
      $ting = $this->nodeHelper->loadTingObject($identifier);
      if ($ting) {
        $relations = $ting->getRelations();
        $relation = reset($relations);
        return [
          'guid' => $this->getGuid($paragraph),
          'type' => $this->getType($paragraph),
          'identifier' => $identifier,
          'title' => $ting->getTitle(),
          'url' => $relation ? $relation->getURI() : self::VALUE_NONE,
          'metadata' => [
            'length' => self::VALUE_NONE,
            'filesize' => self::VALUE_NONE,
            'format' => self::VALUE_NONE,
          ],
        ];
      }
    }

    return NULL;
  }

  /**
   * Get guid (generally unique id) for a paragraph.
   *
   * The guid is NOT guaranteed bo be globally unique.
   */
  protected function getGuid(\ParagraphsItemEntity $paragraph, $delta = NULL) {
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
      case self::PARAGRAPH_ALIAS_AUDIO:
        return 'audio_sample';

      case self::PARAGRAPH_AUTHOR_PORTRAIT:
        return 'author_portrait';

      case self::PARAGRAPH_AUTHOR_QUOTE:
        return 'author_quote';

      case self::PARAGRAPH_ALIAS_CAROUSEL:
        return 'carousel';

      case self::PARAGRAPH_ALIAS_EDITOR:
        return 'editor';

      case self::PARAGRAPH_ALIAS_LINK:
        return 'link';

      case self::PARAGRAPH_REVIEW:
        return 'review';

      case self::PARAGRAPH_SPOTLIGHT_BOX:
        return 'spotlight_box';

      case self::PARAGRAPH_ALIAS_THEME:
        return 'theme';

      case self::PARAGRAPH_ALIAS_THEME_LIST:
        return 'theme_list';

      case self::PARAGRAPH_VIDEO:
        return 'video';
    }

    return NULL;
  }

  /**
   * Get view for a paragraph.
   */
  private function getView(\ParagraphsItemEntity $paragraph) {
    $bundle = $paragraph->bundle();

    switch ($bundle) {
      case self::PARAGRAPH_ALIAS_THEME_LIST:
      case self::PARAGRAPH_AUTHOR_PORTRAIT:
      case self::PARAGRAPH_AUTHOR_QUOTE:
      case self::PARAGRAPH_ALIAS_CAROUSEL:
      case self::PARAGRAPH_ALIAS_EDITOR:
      case self::PARAGRAPH_ALIAS_LINK:
      case self::PARAGRAPH_REVIEW:
      case self::PARAGRAPH_SPOTLIGHT_BOX:
      case self::PARAGRAPH_ALIAS_THEME:
      case self::PARAGRAPH_VIDEO:
        return self::VIEW_DOTTED;
    }

    return self::VIEW_SCROLL;
  }

  /**
   * Get real paragraph type from an alias.
   */
  public function getParagraphType($alias) {
    switch ($alias) {
      case 'audio':
        return self::PARAGRAPH_ALIAS_AUDIO;

      case 'author_portrait':
        return self::PARAGRAPH_AUTHOR_PORTRAIT;

      case 'author_quote':
        return self::PARAGRAPH_AUTHOR_QUOTE;

      case 'carousel':
        return self::PARAGRAPH_ALIAS_CAROUSEL;

      case 'editor':
        return self::PARAGRAPH_ALIAS_EDITOR;

      case 'link':
        return self::PARAGRAPH_ALIAS_LINK;

      case 'picked_article_carousel':
        return self::PARAGRAPH_ALIAS_THEME_LIST;

      case 'review':
        return self::PARAGRAPH_REVIEW;

      case 'spotlight_box':
        return self::PARAGRAPH_SPOTLIGHT_BOX;

      case 'theme':
        return self::PARAGRAPH_ALIAS_THEME;

      case 'video':
        return self::PARAGRAPH_VIDEO;
    }

    return $alias;
  }

  /**
   * Check if an array is associative.
   *
   * @param array $arr
   *   The array to check.
   *
   * @return bool
   *   True iff the array is associative.
   *
   * @see https://stackoverflow.com/a/173479
   */
  private function isAssoc(array $arr = NULL) {
    if (NULL === $arr || [] === $arr) {
      return FALSE;
    }
    ksort($arr);
    return array_keys($arr) !== range(0, count($arr) - 1);
  }

}
