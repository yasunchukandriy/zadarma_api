<?php

namespace Drupal\kp_zadarma\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rest\Plugin\Type\ResourcePluginManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'KP Zadarma' block.
 *
 * @Block(
 *   id = "kp_zadarma_block",
 *   category = @Translation("Custom Blocks"),
 *   admin_label = @Translation("KP Zadarma Block"),
 * )
 */
class KpZadarmaBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * The REST resource plugin manager.
   *
   * @var \Drupal\rest\Plugin\Type\ResourcePluginManager
   */
  protected ResourcePluginManager $restManager;

  /**
   * Constructs a new KpZadarmaBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\rest\Plugin\Type\ResourcePluginManager $restManager
   *   The REST resource plugin manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    StateInterface $state,
    ResourcePluginManager $restManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
    $this->restManager = $restManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state'),
      $container->get('plugin.manager.rest')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
        'block_settings' => [
          'weekday' => [
            'enabled' => FALSE,
            'time_to' => '',
            'time_from' => '',
          ],
          'day_off' => [
            'enabled' => FALSE,
            'time_to' => '',
            'time_from' => '',
          ],
        ],
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->configuration['block_settings'];

    foreach (['weekday', 'day_off'] as $period) {
      $form[$period] = [
        '#type' => 'fieldset',
        '#title' => $this->t(ucfirst(str_replace('_', ' ', $period))),
      ];
      $form[$period]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enabled'),
        '#default_value' => $config[$period]['enabled'] ?? FALSE,
        '#description' => $this->t('Show block during selected time'),
      ];
      $form[$period]['container_' . $period] = [
        '#tree' => TRUE,
        '#type' => 'fieldset',
        '#states' => [
          'visible' => [
            ':input[name="settings[' . $period . '][enabled]"]' => ['checked' => TRUE],
          ],
        ],
      ];
      // Time fields.
      $time_from = $this->buildTimeElement('time_from', $config[$period]['time_from'] ?? '');
      $form[$period]['container_' . $period]['time_from'] = $time_from;

      $time_to = $this->buildTimeElement('time_to', $config[$period]['time_to'] ?? '');
      $form[$period]['container_' . $period]['time_to'] = $time_to;
    }

    return $form;
  }

  /**
   * Saves the block settings after the form is submitted.
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    foreach (['weekday', 'day_off'] as $period) {
      $container = $form_state->getValue([$period, 'container_' . $period]);

      $time_to = !empty($container['time_to']) ? gmdate('H:i', (int) $container['time_to']) : '';
      $time_from = !empty($container['time_from']) ? gmdate('H:i', (int) $container['time_from']) : '';

      $this->configuration['block_settings'][$period] = [
        'enabled' => (bool) $form_state->getValue([$period, 'enabled']),
        'time_to' => $time_to,
        'time_from' => $time_from,
      ];
    }
  }

  /**
   * Validates the block form fields for correct time input.
   */
  public function blockValidate($form, FormStateInterface $form_state): void {
    foreach (['weekday', 'day_off'] as $period) {
      $enabled = (bool) $form_state->getValue([$period, 'enabled']);
      $container = $form_state->getValue([$period, 'container_' . $period]);

      if ($enabled) {
        $time_to = $container['time_to'] ?? '';
        $time_from = $container['time_from'] ?? '';

        if (empty($time_from) || empty($time_to)) {
          $form_state->setErrorByName(
            "settings[$period][container_$period][time_from]",
            $this->t('Both Time from and Time to are required for @period.', ['@period' => $period])
          );
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Checking the connection to the API.
    if (!$this->state->get('kp_zadarma_api_status_connect', FALSE)) {
      return [];
    }

    $data_settings = [];
    foreach ($this->configuration['block_settings'] as $index => $item) {
      if (!empty($item['enabled'])) {
        $data_settings[$index] = [
          'to' => $item['time_to'] ?? NULL,
          'from' => $item['time_from'] ?? NULL,
        ];
      }
    }

    // Generate a unique ID.
    $attr_id = 'kp_zadarma_wrapper_' . implode('_', [$this->getPluginId(), uniqid()]);

    // Get url.
    $definition = $this->restManager->getDefinition('kp_zadarma_callback');

    $uri_paths = $definition['uri_paths'] ?? [];
    $create_path = $uri_paths['create'] ?? NULL;

    return [
      '#type' => 'inline_template',
      '#template' => '<div id="{{ id }}"></div>',
      '#context' => [
        'id' => $attr_id,
      ],
      '#attached' => [
        'library' => [
          'kp_zadarma/kp-zadarma-vue',
        ],
        'drupalSettings' => [
          'kp_zadarma' => [
            $attr_id => [
              'url' => $create_path,
              'attr_id' => $attr_id,
              'settings' => $data_settings,
              'phone_key' => 'zadarma_phone_number',
            ],
          ],
        ],
      ],
      '#cache' => [
        'max-age' => 0,
        'tags' => ['config:kp_zadarma.settings'],
      ],
    ];
  }

  /**
   * Builds a time-only datetime form element.
   *
   * @param string $name
   *   The machine name of the element (used for title generation).
   * @param string|null $value
   *   The default time value in 'H:i' format, or NULL if none.
   *
   * @return array
   *   A renderable form element array for a time-only datetime field.
   */
  private function buildTimeElement(string $name, ?string $value): array {
    return [
      '#type' => 'time',
      '#title' => $this->t(ucfirst(str_replace('_', ' ', $name))),
      '#required' => FALSE,
      '#time_format' => '24h',
      '#default_value' => $value,
    ];
  }

}
