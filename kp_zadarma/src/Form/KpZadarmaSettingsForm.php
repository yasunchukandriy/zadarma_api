<?php

namespace Drupal\kp_zadarma\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\kp_zadarma\ZadarmaApiService;
use Drupal\kp_zadarma\PhoneNumberValidator;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure kp zadarma settings for this site.
 *
 * @internal
 */
class KpZadarmaSettingsForm extends ConfigFormBase {

  /**
   * Configuration name.
   */
  const CONFIG_NAME = 'kp_zadarma.settings';

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The KP Zadarma API service.
   *
   * @var \Drupal\kp_zadarma\ZadarmaApiService
   */
  protected ZadarmaApiService $apiService;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The phone number validator service for validating phone numbers.
   *
   * @var \Drupal\kp_zadarma\PhoneNumberValidator
   */
  protected PhoneNumberValidator $phoneNumberValidator;

  /**
   * Constructs a new KpZadarmaSettingsForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed configuration manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\kp_zadarma\ZadarmaApiService $apiService
   *   The Zadarma API service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\kp_zadarma\PhoneNumberValidator $phone_number_validator
   *   The phone number validator service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config_manager,
    StateInterface $state,
    MessengerInterface $messenger,
    ZadarmaApiService $apiService,
    LoggerChannelFactoryInterface $logger_factory,
    PhoneNumberValidator $phone_number_validator,
  ) {
    parent::__construct($config_factory, $typed_config_manager);

    $this->state = $state;
    $this->messenger = $messenger;
    $this->apiService = $apiService;
    $this->loggerFactory = $logger_factory;
    $this->phoneNumberValidator = $phone_number_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('state'),
      $container->get('messenger'),
      $container->get('kp_zadarma.api_service'),
      $container->get('logger.factory'),
      $container->get('kp_zadarma.phone_number_validator'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'kp_zadarma_form_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $settings = $this->config(self::CONFIG_NAME);

    // API settings.
    $form['zadarma_api_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Zadarma API settings'),
    ];
    $form['zadarma_api_settings']['zadarma_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#required' => TRUE,
      '#default_value' => $settings->get('zadarma_api_key'),
    ];
    $existing_secret = $settings->get('zadarma_api_secret');
    $form['zadarma_api_settings']['zadarma_api_secret'] = [
      '#type' => 'password',
      '#title' => $this->t('API secret'),
      '#required' => empty($existing_secret),
      '#description' => $existing_secret ? $this->t('Leave blank to keep the current secret.') : '',
    ];
    $form['zadarma_api_settings']['zadarma_api_from'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API parameter from'),
      '#required' => TRUE,
      '#description' => $this->t('your phone/SIP number, the PBX extension number or the PBX scenario, to which the CallBack is made;'),
      '#default_value' => $settings->get('zadarma_api_from'),
    ];
    $form['zadarma_api_settings']['zadarma_api_predicted'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('API parameter predicted'),
      '#description' => $this->t('If this flag is specified the request is predicted (the system calls the “to” number, and only connects it to your SIP, or your phone number, if the call is successful.)'),
      '#default_value' => $settings->get('zadarma_api_predicted'),
    ];

    // Connection status message.
    $status = $this->state->get('kp_zadarma_api_status_connect', FALSE);
    $this->messenger->addStatus($status ? $this->t('Connection successful!') : $this->t('Connection failed!'));

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Validate API From number.
    if (!$this->phoneNumberValidator->isValidPhoneNumber($values['zadarma_api_from'])) {
      $form_state->setErrorByName('zadarma_api_from', $this->t('Invalid From number/SIP format.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config(self::CONFIG_NAME);

    $keys = [
      'zadarma_api_key',
      'zadarma_api_from',
      'zadarma_api_predicted',
    ];

    // Saving the main API settings.
    foreach ($keys as $key) {
      $config->set($key, $values[$key]);
    }

    // Only update the secret if a new value was provided.
    if (!empty($values['zadarma_api_secret'])) {
      $config->set('zadarma_api_secret', $values['zadarma_api_secret']);
    }

    $config->save();

    // Update connection status.
    $status = $this->apiService->testConnection();
    $this->state->set('kp_zadarma_api_status_connect', $status);

    $this->messenger->addStatus(
      $status ? $this->t('API connection successful.') : $this->t('API connection failed.')
    );

    parent::submitForm($form, $form_state);
  }

}
