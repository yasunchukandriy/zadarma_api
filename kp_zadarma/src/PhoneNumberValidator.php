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
   * Constructs a new PhoneNumberValidator.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory for accessing system configuration.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory for logging validation errors.
   */
  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Validates a phone number.
   *
   * Checks if the provided phone number is valid by parsing it with
   * libphonenumber and verifying its format and validity for a specific region.
   *
   * @param string|null $phone_number
   *   The phone number to validate (e.g., "+380501234567", "0501234567").
   * @param string|null $region
   *   The region code for validation (e.g., 'UA'). Defaults to auto-detection.
   *
   * @return bool
   *   TRUE if the phone number is valid, FALSE otherwise.
   */
  public function isValidPhoneNumber(?string $phone_number, ?string $region = NULL): bool {
    if (empty($phone_number)) {
      return FALSE;
    }

    $phoneUtil = PhoneNumberUtil::getInstance();

    try {
      $number = $phoneUtil->parse($phone_number, $region);
      return $phoneUtil->isValidNumber($number);
    }
    catch (\Exception $e) {
      if ($this->configFactory->get('system.logging')->get('error_level') === 'verbose') {
        $this->loggerFactory->get('kp_zadarma')->debug('Phone number validation failed: @message', ['@message' => $e->getMessage()]);
      }
      return FALSE;
    }
  }

}
