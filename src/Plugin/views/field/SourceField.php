<?php

namespace Drupal\elasticsearch_helper_views\Plugin\views\field;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;
use Drupal\Core\Cache\Cache;

/**
 * Renders a plain value from the elasticsearch result.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("eshv_sourcefield")
 */
class SourceField extends ElasticsearchHelperViewsFieldPluginBase {


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
  public function render(ResultRow $row) {
    $source_field = $this->options['source_field'];

    list($value, $cardinality) = $this->getNestedValue($row->_source, $source_field);

    if ($cardinality == 'single') {
      $build = $value;
      return $build;
    }
    else {
      return implode(", ", $value);
    }
  }
}
