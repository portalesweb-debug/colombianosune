<?php

namespace Drupal\cnu_content_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\text\TextSummary;

/**
 * Provides "CNU - Noticias Destacadas" block.
 *
 * @Block(
 *   id = "cnu_noticias_destacadas",
 *   admin_label = @Translation("CNU - Noticias Destacadas"),
 *   category = @Translation("ColombianosUNE - Blocks"),
 * )
 */
class CnuNoticiasDestacadasBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected $entityTypeManager;
  protected $dateFormatter;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    DateFormatterInterface $date_formatter,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
    );
  }

  /**
   * Formulario del bloque (cantidad a mostrar).
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['block_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Título del bloque'),
      '#default_value' => $config['block_title'] ?? $this->t('Noticias destacadas'),
      '#maxlength' => 255,
    ];

    $form['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Número de noticias a mostrar'),
      '#default_value' => $config['limit'] ?? 3,
      '#min' => 1,
      '#max' => 20,
    ];

    return $form;
  }

  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('limit', $form_state->getValue('limit'));
    $this->setConfigurationValue('block_title', $form_state->getValue('block_title'));
  }

  /**
   * Build del bloque.
   */
  public function build() {
    $config = $this->getConfiguration();
    $limit = $config['limit'] ?? 3;
    $block_title = $config['block_title'] ?? '';

    // Query: últimas N noticias.
    $nids = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('type', 'noticias')
      ->sort('created', 'DESC')
      ->range(0, $limit)
      ->accessCheck(TRUE)
      ->execute();

    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

    $items = [];

    foreach ($nodes as $node) {
      $item = [
        'title'   => $node->label(),
        'url'     => $node->toUrl()->toString(),
        'summary' => '',
        'date'    => $this->dateFormatter->format($node->getCreatedTime(), 'custom', 'F d, Y'),
        'image'   => '',
      ];

      // Descripción (body)
      if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
        $body = $node->get('body')->first();

        // Obtener texto base: resumen editorial o body completo
        $text = !empty($body->summary)
          ? $body->summary
          : $body->value;

        // Quitar HTML para truncar con seguridad
        $plain_text = trim(strip_tags($text));

        // Truncar a 130 caracteres sin cortar palabras
        if (mb_strlen($plain_text) > 130) {
          $plain_text = mb_substr($plain_text, 0, 130);
          $plain_text = preg_replace('/\s+\S*$/u', '', $plain_text);
          $plain_text .= '…';
        }

        // Volver a procesar el texto con el formato original
        $item['summary'] = [
          '#type' => 'processed_text',
          '#text' => $plain_text,
          '#format' => $body->format,
        ];
      }

      // Imagen
      if ($node->hasField('field_imagen') && !$node->get('field_imagen')->isEmpty()) {
        $file = $node->get('field_imagen')->entity;
        if ($file) {
          $item['image'] = \Drupal::service('file_url_generator')
            ->generateAbsoluteString($file->getFileUri());
        }
      }

      $items[] = $item;
    }

    $count = count($items);

    return [
      '#theme' => 'cnu_noticias_destacadas',
      '#items' => $items,
      '#custom_title' => $block_title,
      '#items_count' => $count,
      '#attached' => [
        'library' => ['cnu_content_blocks/cnu_content_blocks'],
      ],
    ];
  }

  public function defaultConfiguration() {
    return [
      'limit' => 3,
      'block_title' => 'Noticias destacadas',
    ];
  }

}
