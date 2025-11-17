<?php

namespace Drupal\kp_zadarma;

use Zadarma_API\Client;
use Zadarma_API\ApiException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service for interacting with the Zadarma API.
 *
 * Encapsulates the Zadarma API client and provides
 * higher-level methods for checking connection status
 * and handling API errors.
 */
class ZadarmaApiService {

  /**
   * The Zadarma API client.
   *
   * @var Client|null
   */
  protected ?Client $client;

  /**
   * The logger factory.
   *
   * @var LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new ZadarmaApiService object.
   *
   * @param Client|null $client
   *   The Zadarma API client or NULL if not available.
   * @param LoggerChannelFactoryInterface $logger_factory
   * *   The logger factory.
 */
  public function __construct(?Client $client, LoggerChannelFactoryInterface $logger_factory) {
    $this->client = $client;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Checks the API connection by requesting the balance.
   *
   * @return bool
   *   TRUE if the API connection is successful, FALSE otherwise.
   *
   * @note
   *   Logs an error if the client is missing or if the API call fails.
   */
  public function testConnection(): bool {
    if (!$this->client) {
      $this->loggerFactory->get('kp_zadarma')->error('Zadarma API client not available.');
      return FALSE;
    }

    try {
      $rawResponse = $this->client->call('/v1/info/balance/', [], 'GET');
      $response = json_decode($rawResponse, TRUE);

      if (!is_array($response) || ($response['status'] ?? '') !== 'success') {
        throw new ApiException('Invalid response: ' . ($response['message'] ?? 'Unknown error'));
      }

      $this->loggerFactory->get('kp_zadarma')->info('Zadarma API connection successful. Balance: @balance @currency', [
        '@balance' => $response['balance'] ?? 'N/A',
        '@currency' => $response['currency'] ?? 'N/A',
      ]);
      $status = TRUE;
    }
    catch (ApiException $e) {
      $this->loggerFactory->get('kp_zadarma')->error('Zadarma API connection failed: @msg', ['@msg' => $e->getMessage(), 'exception' => $e]);
      $status = FALSE;
    }

    return $status;
  }

}
