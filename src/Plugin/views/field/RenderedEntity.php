<?php

namespace Drupal\elasticsearch_helper_views\Plugin\views\field;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Entity\Render\EntityTranslationRenderTrait;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a field handler which renders an entity in a certain view mode.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("elasticsearch_rendered_entity")
 */
class RenderedEntity extends FieldPluginBase implements CacheableDependencyInterface {

  use EntityTranslationRenderTrait;

  /** @var \Drupal\Core\Entity\EntityManagerInterface $entityManager */
  protected $entityManager;

  /**
   * RenderedEntity constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param array $plugin_definition
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    $options['settings']['default'] = '';
    $options['entity-store']['default'] = 'entity-store-' . uniqid();
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['settings'] = [
      '#type' => 'textarea',
      '#title' => $this->t("Settings"),
      '#default_value' => Yaml::encode($this->options['settings']),
      '#description' => $this->t('Provide YAML settings mapping entity/bundle: '
        ),
    ];
  }

    public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);
    $value = &$form_state->getValue(['options','settings']);

    if (empty($value)) {
      $value = '';
    }
    try {
      Yaml::decode($value);
    } catch (\Exception $e) {
      $form_state->setError(['options','settings'], $this->t('Please enter valid JSON'));
    }
  }

  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    $value = Yaml::decode($form_state->getValue(['options','settings']));
    $form_state->setValue(['options','settings'], $value);
    parent::submitOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    #dpm($values);
    $entity_store_id = $this->options['entity-store'];

    $build = [];
    $entities = $values->$entity_store_id;

    //TODO! deal with multiple entities!
    $entity = array_pop($entities);

    // Elasticsearch results might not correspond to a Drupal entity.
    if ($entity instanceof ContentEntityInterface) {
      $entity = $this->getEntityTranslation($entity, $values);
      if (isset($entity)) {
        $access = $entity->access('view', NULL, TRUE);
        $build['#access'] = $access;
        if ($access->isAllowed()) {
          $entity_type = $entity->getEntityTypeId();
          $entity_bundle = $entity->bundle();
          $mode_mode = $this->pick_view_mode($entity_type, $entity_bundle);
          $view_builder = $this->entityManager->getViewBuilder($entity_type);
          $build += $view_builder->view($entity, $mode_mode);
        }
      }
    }

    return $build;
  }

  protected function pick_view_mode($type, $bundle) {
    $settings = $this->options['settings'];
    if (isset($settings["$type:$bundle"]['view_mode'])) {
      return $settings["$type:$bundle"]['view_mode'];
    }
    if (isset($settings["$type"]['view_mode'])) {
      return $settings["$type"]['view_mode'];
    }
    return 'default';
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
    $view_display_storage = $this->entityManager->getStorage('entity_view_display');
    $view_displays = $view_display_storage->loadMultiple($view_display_storage
      ->getQuery()
      ->condition('targetEntityType', $this->getEntityTypeId())
      ->execute());

    $tags = [];
    foreach ($view_displays as $view_display) {
      $tags = array_merge($tags, $view_display->getCacheTags());
    }
    return $tags;
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
    $this->view->build_info['load_entities'] = [
      $this->options['entity-store'] => $this->options['settings'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    if (($row_index = $this->view->row_index) !== NULL) {
      return $this->view->result[$row_index]->_entity->getEntityTypeId();
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityManager() {
    return $this->entityManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLanguageManager() {
    return $this->languageManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getView() {
    return $this->view;
  }

}
