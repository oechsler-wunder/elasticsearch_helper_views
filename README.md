# Elasticsearch Helper Views

`elasticsearch_helper_views` is a module that provides a way to display Elasticsearch results using Drupal Views.

The module contains a Views query plugin `Elasticsearch` which acts as a bridge between Views display and Elasticsearch. The module defines an `ElasticsearchQueryBuilder` plugin type and an extendable base implementation which allows building Query DSL definition accepted by [`Elasticsearch`][elasticsearch_client] client.

Given the arbitrary nature of Elasticsearch document properties, it's impossible to create a definitive list of field and filter plugins that cover all the data stored in Elasticsearch. For that reason for each data listing you might want to create your own views field and filter plugins that correspond to specific fields in the index. The same applies to Query DSL builder which you will have to create on your own.

The module provides a `Rendered entity` views field plugin which will display rendered Drupal entities in pre-selected view mode per entity type if Elasticsearch indices are created using [`Elasticsearch Helper`][elasticsearch_helper] module or Elasticsearch indices contain `id` and `entity_type` properties which correspond to ids and entity types in Drupal.

## Usage

1. Install `Elasticsearch` search engine ([how-to][elasticsearch_download]).
2. Install [`Elasticsearch Helper`][elasticsearch_helper] module, configure it and create indices using `ElasticsearchIndex` type plugins in a custom module.
3. Create an `ElasticsearchQueryBuilder` type plugin in a custom module.
4. If you want to create exposed filter in your view, you will have to create custom views filter plugins and define them in a `hook_views_data_alter()` hook for `elasticsearch_result` data type.
5. Create a view and select `Elasticsearch result` as a data type.
4. In a view configuration form click on the _Query settings_ link and select the query building plugin that will generate Query DSL for this particular view.
5. Save the view.

## Composer

Add the following snippet to `composer.json` file and run `composer require maijs/elasticsearch_helper_views 1.x-dev` to have the module added to your project.

```
{
    "repositories": [
        {
            "type": "package",
            "package": {
                "name": "maijs/elasticsearch_helper_views",
                "version": "1.x-dev",
                "type": "drupal-module",
                "require": {
                    "drupal/elasticsearch_helper": "1.x-dev"
                },
                "source": {
                    "url": "https://github.com/maijs/elasticsearch_helper_views.git",
                    "type": "git",
                    "reference": "8.x-1.x"
                }
            }
        }
    ]
}
```

## To-do

- [x] Pager support
- [ ] Contextual filter support
- [ ] Sorting support

[elasticsearch_download]: https://www.elastic.co/downloads/elasticsearch
[elasticsearch_helper]: https://www.drupal.org/project/elasticsearch_helper
[elasticsearch_client]: https://github.com/elastic/elasticsearch-php