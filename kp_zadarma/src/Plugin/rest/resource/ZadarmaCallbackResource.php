<?php

namespace Drupal\kp_zadarma\Plugin\rest\resource;

use Zadarma_API\Client;
use Psr\Log\LoggerInterface;
use Zadarma_API\ApiException;
use Drupal\rest\ResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\Attribute\RestResource;
use Drupal\kp_zadarma\PhoneNumberValidator;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a REST resource for Zadarma callback.
 */
#[RestResource(
  id: "kp_zadarma_callback",
  label: new TranslatableMarkup("Zadarma Callback"),
  uri_paths: [
    "create" => "/api/kp_zadarma/callback",
  ],
)]
class ZadarmaCallbackResource extends ResourceBase {

  /**
   * The Zadarma API client.
   *
   * @var Client|null
   */
  protected $zadarmaClient;

  /**
   * The configuration factory.
   *
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger factory.
   *
   * @var LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The phone number validator service for validating phone numbers.
   *
   * @var PhoneNumberValidator
   */
  protected $phoneNumberValidator;

  /**
   * Constructs a new ZadarmaCallbackResource.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param Client|null $zadarma_client
   *   The Zadarma API client.
   * @param ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param PhoneNumberValidator $phone_number_validator
   *   The phone number validator service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    $serializer_formats,
    LoggerInterface $logger,
    ?Client $zadarma_client,
    ConfigFactoryInterface $config_factory,
    PhoneNumberValidator $phone_number_validator
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->zadarmaClient = $zadarma_client;
    $this->configFactory = $config_factory;
    $this->phoneNumberValidator = $phone_number_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('kp_zadarma'),
      $container->get('kp_zadarma.api_client'),
      $container->get('config.factory'),
      $container->get('kp_zadarma.phone_number_validator')
    );
  }

  /**
   * Handles POST requests for Zadarma callback.
   */
  public function post(Request $request) {
    if (!$this->zadarmaClient) {
      $this->logger->error('Zadarma API library is missing.');
      return new ResourceResponse([
        'status' => 'error',
        'message' => 'Zadarma API library is missing.',
      ], 500);
    }

    $data = json_decode($request->getContent(), TRUE);

    $phone_number = $data['zadarma_phone_number'] ?? '';
    if (!$this->phoneNumberValidator->isValidPhoneNumber($phone_number)) {
      $this->logger->warning($this->t('Invalid phone number: @number', ['@number' => (string) $phone_number]));
      return new ResourceResponse([
        'status' => 'error',
        'message' => 'Invalid phone number.',
      ], 400);
    }

    try {
      $config = $this->configFactory->get('kp_zadarma.settings');
      $params = [
        'to' => $phone_number,
        'from' => $config->get('zadarma_api_from'),
        'predicted' => $config->get('zadarma_api_predicted') ? '1' : '0',
      ];

      $answer = $this->zadarmaClient->call('/v1/request/callback/', $params);
      $response = [
        'data' => json_decode($answer, TRUE) ?: $answer,
        'status' => 'success',
      ];
      $this->logger->info($this->t('Callback request successful for phone: @number', ['@number' => $phone_number]));
      return new ResourceResponse($response, 200);
    }
    catch (ApiException $e) {
      $this->logger->error($this->t('Zadarma API request failed: @message', ['@message' => $e->getMessage()]));
      return new ResourceResponse([
        'status' => 'error',
        'message' => t('API request failed: @error', ['@error' => $e->getMessage()]),
      ], 500);
    }
  }

}
