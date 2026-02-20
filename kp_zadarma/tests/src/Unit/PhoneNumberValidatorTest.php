<?php

namespace Drupal\Tests\kp_zadarma\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\kp_zadarma\PhoneNumberValidator;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Tests the PhoneNumberValidator service.
 *
 * @group kp_zadarma
 * @coversDefaultClass \Drupal\kp_zadarma\PhoneNumberValidator
 */
class PhoneNumberValidatorTest extends UnitTestCase {

  /**
   * The phone number validator under test.
   *
   * @var \Drupal\kp_zadarma\PhoneNumberValidator
   */
  protected PhoneNumberValidator $validator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->with('error_level')
      ->willReturn('verbose');

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')
      ->with('system.logging')
      ->willReturn($config);

    $logger = $this->createMock(LoggerChannelInterface::class);

    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')
      ->with('kp_zadarma')
      ->willReturn($logger);

    $this->validator = new PhoneNumberValidator($configFactory, $loggerFactory);
  }

  /**
   * Tests valid international phone numbers.
   *
   * @dataProvider validPhoneNumbersProvider
   */
  public function testValidPhoneNumbers(string $phone, ?string $region = NULL): void {
    $this->assertTrue($this->validator->isValidPhoneNumber($phone, $region));
  }

  /**
   * Data provider for valid phone numbers.
   */
  public static function validPhoneNumbersProvider(): array {
    return [
      'Ukrainian mobile international' => ['+380501234567'],
      'US number international' => ['+12025551234'],
      'German number international' => ['+4930123456'],
      'Ukrainian with region hint' => ['0501234567', 'UA'],
    ];
  }

  /**
   * Tests invalid phone numbers.
   *
   * @dataProvider invalidPhoneNumbersProvider
   */
  public function testInvalidPhoneNumbers(?string $phone, ?string $region = NULL): void {
    $this->assertFalse($this->validator->isValidPhoneNumber($phone, $region));
  }

  /**
   * Data provider for invalid phone numbers.
   */
  public static function invalidPhoneNumbersProvider(): array {
    return [
      'null value' => [NULL],
      'empty string' => [''],
      'random text' => ['not-a-phone'],
      'too short' => ['+123'],
      'too long' => ['+1234567890123456789'],
      'only plus sign' => ['+'],
      'letters mixed' => ['+380abc1234567'],
    ];
  }

  /**
   * Tests that empty/null inputs return FALSE immediately.
   *
   * @covers ::isValidPhoneNumber
   */
  public function testEmptyInputReturnsFalse(): void {
    $this->assertFalse($this->validator->isValidPhoneNumber(NULL));
    $this->assertFalse($this->validator->isValidPhoneNumber(''));
  }

}
