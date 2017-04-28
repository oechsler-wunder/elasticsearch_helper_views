<?php

namespace Drupal\elasticsearch_helper_views\Plugin\views\query;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\elasticsearch_helper_views\ElasticsearchQueryBuilderInterface;
use Drupal\elasticsearch_helper_views\ElasticsearchQueryBuilderManager;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Elasticsearch\Client;

/**
 * Views query plugin for an Elasticsearch query.
 *
 * @ingroup views_query_plugins
 *
 * @ViewsQuery(
 *   id = "elasticsearch_query",
 *   title = @Translation("Elasticsearch Query"),
 *   help = @Translation("Query will be generated and run using the Elasticsearch API.")
 * )
 */
class Elasticsearch extends QueryPluginBase {

  /** @var \Elasticsearch\Client $elasticsearchClient */
  protected $elasticsearchClient;

  /** @var  EntityTypeManagerInterface $entityTypeManager */
  protected $entityTypeManager;

  /** @var \Drupal\elasticsearch_helper_views\ElasticsearchQueryBuilderManager $elasticsearchQueryBuilderManager */
  protected $elasticsearchQueryBuilderManager;

  /**
   * Elasticsearch constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Elasticsearch\Client $elasticsearch_client
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\elasticsearch_helper_views\ElasticsearchQueryBuilderManager $query_builder_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Client $elasticsearch_client, EntityTypeManagerInterface $entity_type_manager, ElasticsearchQueryBuilderManager $query_builder_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->elasticsearchClient = $elasticsearch_client;
    $this->entityTypeManager = $entity_type_manager;
    $this->elasticsearchQueryBuilderManager = $query_builder_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('elasticsearch_helper.elasticsearch_client'),
      $container->get('entity_type.manager'),
      $container->get('elasticsearch_query_builder.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setOptionDefaults(array &$storage, array $options) {
    parent::setOptionDefaults($storage, $options);
    $storage['elasticserach_query_builder'] = '';
  }

  /**
   * Provides query options form.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $query_builder_options = [];
    foreach ($this->elasticsearchQueryBuilderManager->getDefinitions() as $query_builder_plugin) {
      $query_builder_options[$query_builder_plugin['id']] = sprintf('%s (%s)', $query_builder_plugin['label'], $query_builder_plugin['id']);
    }

    $form['elasticserach_query_builder'] = array(
      '#type' => 'select',
      '#title' => $this->t('Elasticsearch query builder'),
      '#empty_value' => '',
      '#options' => $query_builder_options,
      '#default_value' => $this->options['elasticserach_query_builder'],
      '#required' => FALSE,
    );
  }

  /**
   * Builds the necessary info to execute the query.
   */
  public function build(ViewExecutable $view) {
    // Store the view in the object to be able to use it later.
    $this->view = $view;

    // Initiate pager.
    $view->initPager();

    // Let the pager modify the query to add limits.
    $view->pager->query();

    $view->build_info['query'] = $this->query();
  }

  /**
   * Placeholder method.
   *
   * @param $group
   * @param $field
   * @param null $value
   * @param null $operator
   */
  public function addWhere($group, $field, $value = NULL, $operator = NULL) {
  }

  /**
   * Placeholder method.
   *
   * @param $group
   * @param $snippet
   * @param array $args
   */
  public function addWhereExpression($group, $snippet, $args = array()) {
  }

  /**
   * Placeholder method.
   *
   * @param $table
   * @param null $field
   * @param string $order
   * @param string $alias
   * @param array $params
   */
  public function addOrderBy($table, $field = NULL, $order = 'ASC', $alias = '', $params = array()) {
  }

  /**
   * Placeholder method.
   *
   * @param $clause
   */
  public function addGroupBy($clause) {
  }

  /**
   * {@inheritdoc}
   */
  public function query($get_count = FALSE) {
    /** @var ElasticsearchQueryBuilderInterface $query_builder */
    $query_builder = $this->elasticsearchQueryBuilderManager->createInstance($this->options['elasticserach_query_builder']);
    $query = $query_builder->buildQuery($this->view);

    // Apply limit and offset to the query.
    $limits = [
      'size' => $this->getLimit(),
      'from' => $this->offset,
    ];

    return array_merge($limits, $query);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ViewExecutable $view) {
    $query = $view->build_info['query'];
    $data = [];
    $result = [];

    try {
      if ($data = $this->elasticsearchClient->search($query)) {
        $index = 0;
        foreach ($data['hits']['hits'] as $hit) {
          $hit['index'] = $index++;
          $result[] = new ResultRow($hit);
        }
      }
    } catch (\Exception $e) {
      watchdog_exception('elasticsearch_helper_views', $e);
    }

    $view->result = $result;

    $view->pager->postExecute($view->result);
    $view->pager->total_items = isset($data['hits']['total']) ? $data['hits']['total'] : 0;
    $view->pager->updatePageInfo();
    $view->total_rows = $view->pager->getTotalItems();

    if (isset($view->build_info['load_entities'])){
      $this->loadEntities($result, $view->build_info['load_entities']);
    }
  }

  /**
   * Returns an empty array as there's no physical table for Elasticsearch.
   *
   * @param $table
   * @param null $relationship
   *
   * @return string
   */
  public function ensureTable($table, $relationship = NULL) {
    return '';
  }

  /**
   * Returns the field as is as there's no need to limit fields in result set.
   *
   * @param $table
   * @param $field
   * @param string $alias
   * @param array $params
   *
   * @return mixed
   */
  public function addField($table, $field, $alias = '', $params = array()) {
    return $field;
  }

  /**
   * ...
   */
  public function loadEntities(&$results, $fields_settings) {
    $entity_ids_by_type = [];

    foreach ($results as $index => $result) {
      // Iterate over all the relevant setting.
      foreach ($fields_settings as $entity_store_id => $field_settings) {
        $result->_entity_pointer[$entity_store_id] = [];
        // $field_settings may have multiple parts.
        foreach ($field_settings as $setting) {
          if (isset($setting['entity_type'])) {
              $entity_type = $setting['entity_type'];
          }
          else {
            $entity_type = NestedArray::getValue($result->_source, array_map('trim', explode('][', $setting['entity_type_key'])));
          }
          if (isset($setting['entity_id_key'])) {
            $entity_id = NestedArray::getValue($result->_source, array_map('trim', explode('][', $setting['entity_id_key'])));
          }
          if (!empty($entity_type) && !empty($entity_id)) {
            $result->_entity_pointer[$entity_store_id][] = [$entity_type, $entity_id];
            $entity_ids_by_type[$entity_type][$entity_id] = $entity_id;
            continue 2;
          }
        }
      }
    }

    // Load all entities
    foreach ($entity_ids_by_type as $entity_type => $ids) {
      $entities[$entity_type] = $this->entityTypeManager->getStorage($entity_type)->loadMultiple($ids);
    }

    foreach ($results as $index => $result) {
      foreach ($result->_entity_pointer as $entity_store_id => $entity_pointers) {
        foreach ($entity_pointers as $entity_pointer) {
          list($entity_type, $entity_id) = $entity_pointer;
          $result->$entity_store_id[] = $entities[$entity_type][$entity_id];
        }
      }
    }
  }

  /**
   * Gets all the involved entities of the view.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   */
  protected function getAllEntities() {
    $entities = [];
    foreach ($this->view->result as $row) {
      if ($row->_entity) {
        $entities[] = $row->_entity;
      }
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = [];

    foreach ($this->getAllEntities() as $entity) {
      $tags = Cache::mergeTags($entity->getCacheTags(), $tags);
    }

    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    $max_age = parent::getCacheMaxAge();
    foreach ($this->getAllEntities() as $entity) {
      $max_age = Cache::mergeMaxAges($max_age, $entity->getCacheMaxAge());
    }

    return $max_age;
  }

}
