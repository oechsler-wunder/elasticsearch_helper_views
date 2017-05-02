<?php

namespace Drupal\elasticsearch_helper_views\Plugin\views\field;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManagerInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Base class for elasticsearch_helper_views field plugins.
 */
abstract class ElasticsearchHelperViewsFieldPluginBase extends FieldPluginBase implements CacheableDependencyInterface {

  /**
   * The elasticsearch
   *
   * @var Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManagerInterface
   */
  protected $elasticsearchHelperPluginManager;

  /**
   * The constructor.
   * @param array $configuration
   * @param type $plugin_id
   * @param type $plugin_definition
   * @param ElasticsearchIndexManagerInterface $elasticsearchHelperPluginManager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ElasticsearchIndexManagerInterface $elasticsearchHelperPluginManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->elasticsearchHelperPluginManager = $elasticsearchHelperPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.elasticsearch_index.processor')
    );
  }

  /**
   * Extracts and return data from the given $data array at the give $locator
   * (a list of array keys separated by ][, starting with the outermost key)
   *
   * There may be one * in the locator, which indicates, that there are multiple
   * values to be found.
   *
   * It return an array with the found value/values first and an indicator
   * whether it a single or mutiple value.
   *
   * @param array $data
   * @param string $locator
   * @return array($value, 'single'|'multiple')
   */
  public static function getNestedValue($data = [], $locator) {

    // No wildcards in source field: It's just the item at the given position
    if (!preg_match('/\*/', $locator)) {
      $value = NestedArray::getValue($data, array_map('trim', explode('][', $locator)));
      return [$value, 'single'];
    }

    // With wildcard:
    $parts = explode('*', $locator);
    if (count($parts) > 2) {
      $message = $this->t('Max one wildcard allowed in source field definition "$def".', ['%def' => $locator]);
      drupal_set_message($message);
      throw new \Exception($message);
    }

    $first_part = explode('][', $parts[0]);
    array_pop($first_part); // Last element is empty as leftover from *
    $nested_values = NestedArray::getValue($data, array_map('trim', $first_part));

    $second_part = explode('][', $parts[1]);
    array_shift($second_part); // First element is empty as leftover from *
    if (!empty($second_part)) {
      foreach ($nested_values as $nested_value) {
        $value[] = NestedArray::getValue($nested_value, array_map('trim', $second_part));
      }
    }
    return [$value, 'multiple'];
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

}
