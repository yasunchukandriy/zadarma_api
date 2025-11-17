<?php

namespace Drupal\Tests\kp_zadarma\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\kp_zadarma\Plugin\rest\resource\ZadarmaCallbackResource;

/**
 * Tests the Zadarma callback REST resource using KernelTestBase.
 *
 * Kernel tests boot Drupal's service container and configuration system,
 * but without a full browser environment.
 */
class ZadarmaCallbackResourceKernelTest extends KernelTestBase {

  /**
   * Modules required for this test.
   */
  protected static $modules = ['kp_zadarma', 'rest', 'serialization'];

  /**
   * Tests POST request handling of the REST resource.
   */
  public function testPost() {
    $config_factory = $this->container->get(ConfigFactoryInterface::class);

    // Create a mock Zadarma API client.
    $mock_client = $this->createMock(\Zadarma_API\Client::class);
    $mock_client->method('call')->willReturn(json_encode(['result' => 'OK']));

    $resource = new ZadarmaCallbackResource(
      [],
      'kp_zadarma_callback',
      [],
      ['json'],
      $this->container->get('logger.factory')->get('kp_zadarma'),
      $mock_client,
      $config_factory,
      $this->container->get('kp_zadarma.phone_number_validator')
    );

    $request = new \Symfony\Component\HttpFoundation\Request([], [], [], [], [], [], json_encode([
      'zadarma_phone_number' => '+380971234567'
    ]));

    // Execute the resource's POST handler.
    $response = $resource->post($request);

    // Get result request.
    $data = $response->getResponseData();

    // Assert successful processing.
    $this->assertEquals('success', $data['status']);
    $this->assertEquals('OK', $data['data']['result']);
  }

}
