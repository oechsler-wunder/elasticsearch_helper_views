<?php

namespace Drupal\elasticsearch_helper_views\Plugin\ElasticsearchQueryBuilder;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\elasticsearch_helper_views\ElasticsearchQueryBuilderInterface;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Base class for Elasticsearch index plugins.
 */
abstract class ElasticsearchQueryBuilderPluginBase extends PluginBase implements ElasticsearchQueryBuilderInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $this->options = $configuration;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFilterValues(ViewExecutable $view) {
    $values = [];
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    foreach ($view->filter as $filter) {
      $values[$filter->realField] = [
        'value' => $filter->value,
        'options' => $filter->options
      ];
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getArgumentValues(ViewExecutable $view) {
    $arguments = [];
    /** @var \Drupal\views\Plugin\views\argument\ArgumentPluginBase $argument */
    foreach ($view->argument as $argument) {
      $arguments[$argument->realField] = $argument->getValue();
    }
    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  public function getSortValues(ViewExecutable $view) {
    $arguments = [];
    /** @var \Drupal\views\Plugin\views\sort\SortPluginBase $sort */
    foreach ($view->sort as $sort) {
      $arguments[$sort->realField] = strtolower($sort->options['order']);
    }
    return $arguments;
  }

  /**
   * Allow additional options for this query builder.
   *
   * Add form elements to the container element called like the plugin id.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form[$this->pluginId] = [
      '#type' => 'container',
      '#states' => ['visible' => [':input[name="query[options][elasticserach_query_builder]"]' => ['value' => $this->pluginId ]]],
    ];
  }
}
