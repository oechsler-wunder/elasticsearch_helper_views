<?php

namespace Drupal\elasticsearch_helper_views\Plugin\views\field;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Cache\Cache;

/**
 * Renders a plain value from the elasticsearch result.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("eshv_sourcefield")
 */
class SourceField extends FieldPluginBase implements CacheableDependencyInterface {


  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['source_field'] = ['default' => 'title'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['source_field'] = [
     '#type' => 'textfield',
     '#title' => 'Source field',
     '#default_value' => $this->options['source_field']
    ];
  }

  public function adminLabel($short = FALSE) {
     return parent::adminLabel() . " (" . $this->options['source_field'] . ")";
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $source_field = $this->options['source_field'];
    $value = NestedArray::getValue($values->_source, array_map('trim', explode('][', $source_field)));
    $build = ['#markup' => $value];
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
