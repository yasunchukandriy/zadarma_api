<?php

namespace Drupal\kp_zadarma;

use Zadarma_API\Client;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Factory for creating Zadarma API client.
 */
class ZadarmaApiClientFactory {

  /**
   * The configuration factory.
   *
   * @var ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a new ZadarmaApiClientFactory.
   *
   * @param ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Creates a Zadarma API client.
   *
   * @return Client|null
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
