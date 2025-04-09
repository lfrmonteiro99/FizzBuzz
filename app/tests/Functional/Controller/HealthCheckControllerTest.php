<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @codeCoverageIgnore
 */
class HealthCheckControllerTest extends WebTestCase
{
    public function testHealthEndpoint(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        $this->assertResponseIsSuccessful(); // Asserts HTTP 2xx status code
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseContent = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($responseContent);
        $this->assertArrayHasKey('status', $responseContent);
        $this->assertContains($responseContent['status'], ['ok', 'warning']); // Allow 'warning' if memory is high
        $this->assertArrayHasKey('timestamp', $responseContent);
        $this->assertArrayHasKey('version', $responseContent);
        $this->assertArrayHasKey('environment', $responseContent);
        $this->assertArrayHasKey('checks', $responseContent);
        $this->assertIsArray($responseContent['checks']);
        $this->assertArrayHasKey('database', $responseContent['checks']);
        $this->assertArrayHasKey('cache', $responseContent['checks']);
        $this->assertArrayHasKey('memory', $responseContent['checks']);
    }
    
    public function testHealthEndpointWithCustomHeader(): void
    {
        $client = static::createClient();
        $client->request(
            'GET',
            '/health',
            [], // Parameters
            [], // Files
            ['HTTP_X_CUSTOM_HEALTHCHECK' => 'TestValue'] // Server (includes headers)
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseContent = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($responseContent);
        $this->assertArrayHasKey('status', $responseContent);
        $this->assertContains($responseContent['status'], ['ok', 'warning']);
        $this->assertArrayHasKey('checks', $responseContent);
        // Optionally check that the custom header is *not* in the response unless intended
        // $this->assertFalse($client->getResponse()->headers->has('X-Custom-Healthcheck'));
    }
} 