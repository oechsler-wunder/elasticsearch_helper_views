<?php

namespace Drupal\elasticsearch_helper_views\Plugin\ElasticsearchQueryBuilder;

use Drupal\Core\Form\FormStateInterface;
use Drupal\elasticsearch_helper_views\ElasticsearchQueryBuilderInterface;
use Drupal\elasticsearch_helper_views\Plugin\ElasticsearchQueryBuilder\ElasticsearchQueryBuilderPluginBase;
use Drupal\views\ViewExecutable;

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
        "min_score" => isset($this->options['min_score'])?$this->options['min_score']:'0',
        "highlight" => [
          "fragment_size"  => 150,
          "number_of_fragments"  => 3,
          "order"  => "score",
          "fields" => [
            "*" => ((object) null),
          ]
        ],
        "query" => [
          "dis_max" => [
            "queries" => $dis_max_queries,
          ]
        ]
      ]
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form[$this->pluginId]['min_score'] = [
      '#type' => 'textfield',
      '#title' => "Minimal score",
      '#default_value' => $this->options['min_score'],
      '#description' => 'Sets min_score attribute of the query.',
    ];
  }
}
