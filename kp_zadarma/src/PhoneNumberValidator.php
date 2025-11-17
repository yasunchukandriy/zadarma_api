<?php

namespace Drupal\kp_zadarma;

use libphonenumber\PhoneNumberUtil;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Provides phone number validation using libphonenumber.
 */
class PhoneNumberValidator {

  /**
   * The configuration factory service.
   *
   * @var ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The logger factory service.
   *
   * @var LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $loggerFactory;

  /**
   * Constructs a new PhoneNumberValidator.
   *
   * @param ConfigFactoryInterface $config_factory
   *   The configuration factory for accessing system configuration.
   * @param LoggerChannelFactoryInterface $logger_factory
   *   The logger factory for logging validation errors.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Validates a phone number.
   *
   * Checks if the provided phone number is valid by parsing it with libphonenumber
   * and verifying its format and validity for a specific region. Handles null or
   * empty inputs and exceptions gracefully.
   *
   * @param string|null $phone_number
   *   The phone number to validate (e.g., "+380501234567", "0501234567").
   * @param string|null $region
   *   The region code for validation (e.g., 'UA'). If null, defaults to automatic detection.
   *
   * @return bool
   *   TRUE if the phone number is valid, FALSE otherwise.
   */
  public function isValidPhoneNumber(?string $phone_number, ?string $region = NULL): bool {
    // Check if the phone number is empty or null to avoid unnecessary processing.
    if (empty($phone_number)) {
      return FALSE;
    }

    // Get an instance of the libphonenumber utility for phone number validation.
    $phoneUtil = PhoneNumberUtil::getInstance();

    try {
      // Parse the phone number to extract its components and validate its format.
      $number = $phoneUtil->parse($phone_number, $region);
      // Verify if the parsed number is valid for the detected or specified region.
      return $phoneUtil->isValidNumber($number);
    }
    catch (\Exception $e) {
      // Handle parsing errors (e.g., invalid format, unsupported region).
      // Log errors only in verbose mode to avoid flooding logs in production.
      if ($this->configFactory->get('system.logging')->get('error_level') === 'verbose') {
        $this->loggerFactory->get('kp_zadarma')->debug('Phone number validation failed: @message', ['@message' => $e->getMessage()]);
      }
      return FALSE;
    }
  }

}
