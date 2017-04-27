<?php

namespace Drupal\elasticsearch_helper_views\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter to make fulltext searches against multiple fields.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsFilter("multimatch")
 */
class MultiMatch extends FilterPluginBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition, ElasticsearchIndexManager $elasticsearchHelperPluginManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->elasticsearchHelperPluginManager = $elasticsearchHelperPluginManager;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.elasticsearch_index.processor')
    );
  }

  /**
   * @var views_plugin_query_default
   */
  public $query;

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['fields'] = ['default' => []];

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $plugins = $this->elasticsearchHelperPluginManager->getDefinitions();
    foreach ($plugins as $plugin_id => $plugin) {

      $form["esh:" . $plugin_id] = [
        '#type' => 'details',
        '#title' => $this->t('Fields of %label.', ['%label' => $plugin['label']]),
        '#open' => TRUE,
      ];

      $mapping = $this->elasticsearchHelperPluginManager->createInstance($plugin_id)->getMapping();

      foreach ($mapping as $field => $mapping_params) {
        $form["esh:" . $plugin_id][$field] = [
          '#type' => 'checkbox',
          '#title' => $field . " (" . $mapping_params['type'] . ")",
          '#default_value' => isset($this->options['fields'][$plugin_id]['fields'][$field]),
        ];
      }
    }
  }

  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    foreach($form_state->getValue('options') as $key => $values) {
      if (preg_match('/^esh:(.*)/', $key, $match)) {
        $plugin_id = $match[1];
        $plugin = $this->elasticsearchHelperPluginManager->createInstance($plugin_id);
        $index = $plugin->indexNamePattern();
        $type = $plugin->getPluginDefinition()['typeName'];
        foreach ($values as $field => $selected) {
          if ($selected) {
            if (!isset($selected_fields[$plugin_id])) {
              $selected_fields[$plugin_id] = [
                'index' => $index,
                'type' => $type,
              ];
            }
            $selected_fields[$plugin_id]['fields'][$field] = $field;
          }
        }
        $form_state->unsetValue(['options', $key]);
      }
    }
    $this->options['fields'] = $selected_fields;

    parent::submitOptionsForm($form, $form_state);
  }

  public function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
       '#type' => 'textfield',
       '#title' => $this->t('Search query'),
       '#size' => 30,
       '#default_value' => $this->value,
    ];
  }
}
