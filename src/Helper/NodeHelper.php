<?php

namespace Drupal\ereol_app_feed\Helper;

use EntityFieldQuery;

/**
 * Node helper.
 */
class NodeHelper {
  const ENTITY_TYPE_NODE = 'node';
  const ENTITY_TYPE_PARAGRAPH = 'paragraphs_item';

  /**
   * Get value of a field.
   */
  public function getFieldValue($entity, $field_name, $sub_field_name = NULL, $multiple = FALSE) {
    if (!isset($entity->{$field_name}[LANGUAGE_NONE])) {
      return NULL;
    }

    $values = $entity->{$field_name}[LANGUAGE_NONE];

    if (NULL !== $sub_field_name) {
      $values = array_column($values, $sub_field_name);
    }

    return $multiple ? $values : reset($values);
  }

  /**
   * Get text value of a field.
   */
  public function getTextFieldValue($entity, $field_name, $sub_field_name = NULL, $multiple = FALSE) {
    $values = $this->getFieldValue($entity, $field_name, $sub_field_name, TRUE);
    $values = array_map([$this, 'getTextValue'], $values);

    return $multiple ? $values : reset($values);
  }

  /**
   * Get text value.
   */
  private function getTextValue($value) {
    return isset($value['safe_value']) ? $value['safe_value'] : NULL;
  }

  /**
   * Get body from a node.
   */
  public function getBody($node) {
    return $this->getTextFieldValue($node, 'body', NULL, FALSE);
  }

  /**
   * Get image url.
   */
  public function getImage($value, $multiple = FALSE) {
    if (!isset($value[LANGUAGE_NONE])) {
      return NULL;
    }
    $values = $value[LANGUAGE_NONE];
    $uris = array_column($values, 'uri');
    $urls = array_map([$this, 'getUrl'], $uris);

    return $multiple ? $urls : reset($urls);
  }

  /**
   * Get an absolute url from a "public:/" url.
   */
  public function getUrl($url) {
    return file_create_url($url);
  }

  /**
   * Get ting identifiers.
   */
  public function getTingIdentifiers($entity, $field_name) {
    if (!isset($entity->{$field_name}[LANGUAGE_NONE])) {
      return NULL;
    }
    $relations = ting_reference_get_relations('node', $entity);
    $tings = entity_load('ting_object', array_keys($relations));
    $identifiers = array_values(array_map(function ($ting) {
      return $ting->ding_entity_id;
    }, $tings));

    return $identifiers;
  }

  /**
   * Get a ting identifier from a url.
   */
  public function getTingIdentifierFromUrl($url) {
    return preg_match('@/object/(?P<identifier>.+)$@', $url, $matches) ? urldecode($matches['identifier']) : NULL;
  }

  /**
   * Load nodes from references.
   *
   * @param object|\ParagraphsItemEntity $entity
   *   The entity.
   * @param string $field_name
   *   The field name.
   * @param bool $multiple
   *   It set, load multiple references. Otherwise, load just one.
   *
   * @return mixed
   *   A single node or a list of nodes if any.
   */
  public function loadReferences($entity, $field_name, $multiple = TRUE) {
    if (!isset($entity->{$field_name}[LANGUAGE_NONE])) {
      return NULL;
    }
    $values = $entity->{$field_name}[LANGUAGE_NONE];
    $nids = array_column($values, 'target_id');
    $nodes = node_load_multiple($nids);

    return $multiple ? $nodes : reset($nodes);
  }

  /**
   * Load a single node of a specific type by nid.
   *
   * @param string $node_type
   *   The node type.
   * @param int $nid
   *   The node id.
   *
   * @return bool|mixed|null
   *   The node if any.
   */
  public function loadNode($node_type, $nid) {
    $entity_type = self::ENTITY_TYPE_NODE;
    $query = new EntityFieldQuery();
    $query
      ->entityCondition('entity_type', $entity_type)
      ->entityCondition('bundle', $node_type)
      ->propertyCondition('status', NODE_PUBLISHED)
      ->entityCondition('entity_id', $nid);
    $result = $query->execute();

    return isset($result[$entity_type][$nid]) ? node_load($nid) : NULL;
  }

}
