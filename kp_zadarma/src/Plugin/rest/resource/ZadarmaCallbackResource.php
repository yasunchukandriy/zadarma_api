<?php

namespace Drupal\kp_zadarma\Plugin\rest\resource;

use Zadarma_API\Client;
use Psr\Log\LoggerInterface;
use Zadarma_API\ApiException;
use Drupal\Core\Flood\FloodInterface;
use Drupal\rest\ResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\Attribute\RestResource;
use Drupal\kp_zadarma\PhoneNumberValidator;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
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
   * Maximum number of callback requests per IP per hour.
   */
  const FLOOD_LIMIT = 10;

  /**
   * Flood control time window in seconds (1 hour).
   */
  const FLOOD_WINDOW = 3600;

  /**
   * The Zadarma API client.
   *
   * @var \Zadarma_API\Client|null
   */
  protected ?Client $zadarmaClient;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The phone number validator service.
   *
   * @var \Drupal\kp_zadarma\PhoneNumberValidator
   */
  protected PhoneNumberValidator $phoneNumberValidator;

  /**
   * The flood service.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected FloodInterface $flood;

  /**
   * Constructs a new ZadarmaCallbackResource.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Zadarma_API\Client|null $zadarma_client
   *   The Zadarma API client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\kp_zadarma\PhoneNumberValidator $phone_number_validator
   *   The phone number validator service.
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    ?Client $zadarma_client,
    ConfigFactoryInterface $config_factory,
    PhoneNumberValidator $phone_number_validator,
    FloodInterface $flood,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->zadarmaClient = $zadarma_client;
    $this->configFactory = $config_factory;
    $this->phoneNumberValidator = $phone_number_validator;
    $this->flood = $flood;
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
      $container->get('kp_zadarma.phone_number_validator'),
      $container->get('flood'),
    );
  }

  /**
   * Handles POST requests for Zadarma callback.
   */
  public function post(Request $request) {
    // Rate limiting: check flood control by client IP.
    $ip = $request->getClientIp();
    if (!$this->flood->isAllowed('kp_zadarma.callback', self::FLOOD_LIMIT, self::FLOOD_WINDOW, $ip)) {
      $this->logger->warning('Flood control: too many callback requests from IP @ip', ['@ip' => $ip]);
      return new ResourceResponse([
        'status' => 'error',
        'message' => 'Too many requests. Please try again later.',
      ], 429);
    }
    $this->flood->register('kp_zadarma.callback', self::FLOOD_WINDOW, $ip);

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
        'message' => $this->t('API request failed: @error', ['@error' => $e->getMessage()]),
      ], 500);
    }
  }

}
