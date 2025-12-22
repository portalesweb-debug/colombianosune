<?php

namespace Drupal\cnu_content_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a "CNU Enlaces Destacados" Block.
 *
 * @Block(
 *   id = "cnu_enlaces_destacados",
 *   admin_label = @Translation("CNU - Enlaces Destacados"),
 *   category = @Translation("ColombianosUNE - Blocks")
 * )
 */
class CnuEnlacesDestacadosBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected $entityTypeManager;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
    );
  }

  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Número de enlaces a mostrar'),
      '#default_value' => $config['limit'] ?? 4,
      '#min' => 1,
      '#max' => 50,
    ];

    return $form;
  }

  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('limit', $form_state->getValue('limit'));
  }

  public function build() {
    $config = $this->getConfiguration();
    $limit = $config['limit'] ?? 4;

    // Query para obtener nodos del tipo enlaces_detacados.
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'enlaces_detacados')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range(0, $limit)
      ->accessCheck(TRUE)
      ->execute();

    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
    $items = [];

    foreach ($nodes as $node) {
      $item = [
        'title' => $node->label(),
        'text' => '',
        'url' => '',
        'image' => '',
        'des' => '',
      ];

      // Texto
      if ($node->hasField('field_texto_destacado') && !$node->get('field_texto_destacado')->isEmpty()) {
        $item['text'] = $node->get('field_texto_destacado')->value;
      }

      // Des
      if ($node->hasField('field_description') && !$node->get('field_description')->isEmpty()) {
        $item['des'] = $node->get('field_description')->value;
      }

      // Enlace (field_link devuelve un objeto con ->uri y ->title)
      if ($node->hasField('field_enlace_destacado') && !$node->get('field_enlace_destacado')->isEmpty()) {
        $link_field = $node->get('field_enlace_destacado')->first();
        $item['url'] = $link_field->getUrl()->toString();
      }

      // Imagen
      if ($node->hasField('field_imagen_destacados') && !$node->get('field_imagen_destacados')->isEmpty()) {
        $file = $node->get('field_imagen_destacados')->entity;
        if ($file) {
          $item['image'] = \Drupal::service('file_url_generator')
            ->generateAbsoluteString($file->getFileUri());
        }
      }

      $items[] = $item;
    }

    return [
      '#theme' => 'cnu_enlaces_destacados',
      '#items' => $items,
      '#attached' => [
        'library' => [
          // Aquí puedes añadir la librería si necesitas JS o CSS.
        ],
      ],
    ];
  }
}
