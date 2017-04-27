<?php

namespace Drupal\elasticsearch_helper_views\Plugin\ElasticsearchQueryBuilder;

use Drupal\elasticsearch_helper_views\ElasticsearchQueryBuilderInterface;
use Drupal\views\ViewExecutable;
use \Drupal\elasticsearch_helper_views\Plugin\ElasticsearchQueryBuilder\ElasticsearchQueryBuilderPluginBase;

/**
 * Fulltext search query builder.
 *
 * @ElasticsearchQueryBuilder(
 *   id = "fulltext_query_builder",
 *   label = @Translation("Fulltext"),
 *   description = @Translation("Fulltext search query builder")
 * )
 */
class FulltextQueryBuilder extends ElasticsearchQueryBuilderPluginBase implements ElasticsearchQueryBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function buildQuery(ViewExecutable $view) {
    $filterValues = $this->getFilterValues($view);
    $value = $filterValues['eshv_fulltext']['value'];
    $options = $filterValues['eshv_fulltext']['options']['fields'];

    foreach($options as $plugin_id => $data) {
      $dis_max_queries[] = [
        "bool" => [
          "should" => [
            "multi_match" => [
              "query" =>  current($value),
              "fields" => array_keys($data['fields']),
            ]
          ],
          "filter"  => [
            [ "term" => [ "_index" => $data['index'] ]],
            [ "term" => [ "_type" => $data['type'] ]]
          ]
        ]
      ];
    }

    return [
      "index" => "_all",
      "body" => [
        "query" => [
          "dis_max" => [
            "queries" => $dis_max_queries,
          ]
        ]
      ]
    ];

  }
}
