<?php

namespace Drupal\elasticsearch_helper_views\Plugin\views\field;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Cache\Cache;

/**
 * Renders highlights from the search result.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("eshv_highlights")
 */
class HighlightsField extends FieldPluginBase implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['separator'] = ['default' => ' ... '];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['separator'] = [
     '#type' => 'textfield',
     '#title' => 'Separator',
     '#default_value' => $this->options['separator']
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $highlights = [];
    foreach ($values->highlight as $field => $fragments) {
      $highlights = array_merge($highlights, $fragments);
    }
    $build = ['#markup' => implode($this->options['separator'], $highlights)];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
  }
}
