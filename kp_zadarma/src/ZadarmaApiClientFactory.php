<?php

namespace Drupal\kp_zadarma;

use Zadarma_API\Client;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Factory for creating Zadarma API client.
 */
class ZadarmaApiClientFactory {

  /**
   * Constructs a new ZadarmaApiClientFactory.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   */
  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Creates a Zadarma API client.
   *
   * @return \Zadarma_API\Client|null
   *   The Zadarma API client or null if the library is missing.
   */
  public function create(): ?Client {
    if (!class_exists('\Zadarma_API\Client')) {
      return NULL;
    }

    $config = $this->configFactory->get('kp_zadarma.settings');
    return new Client($config->get('zadarma_api_key'), $config->get('zadarma_api_secret'));
  }

}
