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
   * Constructs a new ZadarmaApiService object.
   *
   * @param \Zadarma_API\Client|null $client
   *   The Zadarma API client or NULL if not available.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(
    protected readonly ?Client $client,
    protected readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

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
      $this->loggerFactory->get('kp_zadarma')->error('Zadarma API connection failed: @msg', [
        '@msg' => $e->getMessage(),
        'exception' => $e,
      ]);
      $status = FALSE;
    }

    return $status;
  }

}
