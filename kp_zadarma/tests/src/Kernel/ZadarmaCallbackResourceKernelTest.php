<?php

namespace Drupal\Tests\kp_zadarma\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\kp_zadarma\Plugin\rest\resource\ZadarmaCallbackResource;
use Symfony\Component\HttpFoundation\Request;
use Zadarma_API\ApiException;

/**
 * Tests the Zadarma callback REST resource using KernelTestBase.
 *
 * @group kp_zadarma
 * @coversDefaultClass \Drupal\kp_zadarma\Plugin\rest\resource\ZadarmaCallbackResource
 */
class ZadarmaCallbackResourceKernelTest extends KernelTestBase {

  /**
   * Modules required for this test.
   */
  protected static $modules = ['kp_zadarma', 'rest', 'serialization'];

  /**
   * Creates a ZadarmaCallbackResource instance with given dependencies.
   *
   * @param \Zadarma_API\Client|null $mock_client
   *   The mocked Zadarma API client.
   * @param \Drupal\Core\Flood\FloodInterface|null $flood
   *   The flood service mock. If NULL, a permissive mock is created.
   *
   * @return \Drupal\kp_zadarma\Plugin\rest\resource\ZadarmaCallbackResource
   *   The resource instance.
   */
  protected function createResource($mock_client = NULL, ?FloodInterface $flood = NULL): ZadarmaCallbackResource {
    if (!$flood) {
      $flood = $this->createMock(FloodInterface::class);
      $flood->method('isAllowed')->willReturn(TRUE);
    }

    return new ZadarmaCallbackResource(
      [],
      'kp_zadarma_callback',
      [],
      ['json'],
      $this->container->get('logger.factory')->get('kp_zadarma'),
      $mock_client,
      $this->container->get(ConfigFactoryInterface::class),
      $this->container->get('kp_zadarma.phone_number_validator'),
      $flood,
    );
  }

  /**
   * Creates a Request with JSON body.
   *
   * @param array $data
   *   The request body data.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The request object.
   */
  protected function createRequest(array $data): Request {
    return new Request([], [], [], [], [], ['REMOTE_ADDR' => '127.0.0.1'], json_encode($data));
  }

  /**
   * Tests successful POST request handling.
   */
  public function testPostSuccess(): void {
    $mock_client = $this->createMock(\Zadarma_API\Client::class);
    $mock_client->method('call')->willReturn(json_encode(['result' => 'OK', 'status' => 'success']));

    $resource = $this->createResource($mock_client);
    $request = $this->createRequest(['zadarma_phone_number' => '+380971234567']);
    $response = $resource->post($request);
    $data = $response->getResponseData();

    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals('success', $data['status']);
  }

  /**
   * Tests POST with invalid phone number returns 400.
   */
  public function testPostInvalidPhoneNumber(): void {
    $mock_client = $this->createMock(\Zadarma_API\Client::class);

    $resource = $this->createResource($mock_client);
    $request = $this->createRequest(['zadarma_phone_number' => 'invalid']);
    $response = $resource->post($request);

    $this->assertEquals(400, $response->getStatusCode());
    $this->assertEquals('error', $response->getResponseData()['status']);
  }

  /**
   * Tests POST with missing client returns 500.
   */
  public function testPostMissingClient(): void {
    $resource = $this->createResource(NULL);
    $request = $this->createRequest(['zadarma_phone_number' => '+380971234567']);
    $response = $resource->post($request);

    $this->assertEquals(500, $response->getStatusCode());
    $this->assertStringContainsString('missing', $response->getResponseData()['message']);
  }

  /**
   * Tests POST with API exception returns 500.
   */
  public function testPostApiException(): void {
    $mock_client = $this->createMock(\Zadarma_API\Client::class);
    $mock_client->method('call')->willThrowException(new ApiException('Connection failed'));

    $resource = $this->createResource($mock_client);
    $request = $this->createRequest(['zadarma_phone_number' => '+380971234567']);
    $response = $resource->post($request);

    $this->assertEquals(500, $response->getStatusCode());
    $this->assertEquals('error', $response->getResponseData()['status']);
  }

  /**
   * Tests rate limiting returns 429.
   */
  public function testPostRateLimitExceeded(): void {
    $flood = $this->createMock(FloodInterface::class);
    $flood->method('isAllowed')->willReturn(FALSE);

    $mock_client = $this->createMock(\Zadarma_API\Client::class);
    $resource = $this->createResource($mock_client, $flood);

    $request = $this->createRequest(['zadarma_phone_number' => '+380971234567']);
    $response = $resource->post($request);

    $this->assertEquals(429, $response->getStatusCode());
    $this->assertStringContainsString('Too many requests', $response->getResponseData()['message']);
  }

  /**
   * Tests POST with empty phone number returns 400.
   */
  public function testPostEmptyPhoneNumber(): void {
    $mock_client = $this->createMock(\Zadarma_API\Client::class);

    $resource = $this->createResource($mock_client);
    $request = $this->createRequest(['zadarma_phone_number' => '']);
    $response = $resource->post($request);

    $this->assertEquals(400, $response->getStatusCode());
  }

  /**
   * Tests POST with missing phone key returns 400.
   */
  public function testPostMissingPhoneKey(): void {
    $mock_client = $this->createMock(\Zadarma_API\Client::class);

    $resource = $this->createResource($mock_client);
    $request = $this->createRequest([]);
    $response = $resource->post($request);

    $this->assertEquals(400, $response->getStatusCode());
  }

}
