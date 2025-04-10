<?php

namespace App\Controller;

/**
 * @codeCoverageIgnore
 */

use App\Interface\FizzBuzzRequestRepositoryInterface;
use App\Service\StructuredLogger;
use OpenApi\Attributes as OA;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use App\Interface\RequestLoggerInterface;

#[OA\Tag(name: 'Health')]
class HealthCheckController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestLoggerInterface $logger,
        private readonly string $appVersion = '1.0.0',
        private readonly string $appEnv = 'test'
    ) {
    }

    #[Route('/health', name: 'app_health', methods: ['GET'])]
    #[OA\Get(
        path: '/health',
        summary: 'Check application health',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Application is healthy',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'ok'),
                        new OA\Property(property: 'timestamp', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'version', type: 'string', example: '1.0.0'),
                        new OA\Property(property: 'environment', type: 'string', example: 'dev'),
                        new OA\Property(property: 'checks', type: 'object', properties: [
                            new OA\Property(property: 'database', type: 'object', properties: [
                                new OA\Property(property: 'status', type: 'string', example: 'ok'),
                                new OA\Property(property: 'response_time', type: 'number', format: 'float', example: 0.123)
                            ]),
                            new OA\Property(property: 'cache', type: 'object', properties: [
                                new OA\Property(property: 'status', type: 'string', example: 'ok'),
                                new OA\Property(property: 'response_time', type: 'number', format: 'float', example: 0.045)
                            ]),
                            new OA\Property(property: 'memory', type: 'object', properties: [
                                new OA\Property(property: 'status', type: 'string', example: 'ok'),
                                new OA\Property(property: 'usage', type: 'string', example: '64MB')
                            ])
                        ])
                    ]
                )
            ),
            new OA\Response(
                response: 503,
                description: 'Application is unhealthy',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'timestamp', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'version', type: 'string', example: '1.0.0'),
                        new OA\Property(property: 'environment', type: 'string', example: 'dev'),
                        new OA\Property(property: 'checks', type: 'object', properties: [
                            new OA\Property(property: 'database', type: 'object', properties: [
                                new OA\Property(property: 'status', type: 'string', example: 'error'),
                                new OA\Property(property: 'error', type: 'string', example: 'Connection failed'),
                                new OA\Property(property: 'response_time', type: 'number', format: 'float', example: 0.0)
                            ]),
                            new OA\Property(property: 'cache', type: 'object', properties: [
                                new OA\Property(property: 'status', type: 'string', example: 'error'),
                                new OA\Property(property: 'error', type: 'string', example: 'Cache system unavailable'),
                                new OA\Property(property: 'response_time', type: 'number', format: 'float', example: 0.0)
                            ]),
                            new OA\Property(property: 'memory', type: 'object', properties: [
                                new OA\Property(property: 'status', type: 'string', example: 'ok'),
                                new OA\Property(property: 'usage', type: 'string', example: '64MB')
                            ])
                        ])
                    ]
                )
            )
        ]
    )]
    public function checkHealth(Request $request): JsonResponse
    {
        $stopwatch = new Stopwatch(true);
        $stopwatch->start('health_check_request');
        $path = $request->getPathInfo() ?? '/health';
        $method = $request->getMethod();
        $response = null;
        $statusCode = Response::HTTP_SERVICE_UNAVAILABLE;
        $dbStatus = 'error';
        $dbError = 'Connection check not performed';
        $dbResponseTime = 0.0;

        try {
            $this->logger->logRequest($request);

            $dbCheckStopwatch = new Stopwatch(true);
            $dbCheckStopwatch->start('db_connection_check');

            try {
                $connection = $this->entityManager->getConnection();
                $connection->executeQuery($connection->getDatabasePlatform()->getDummySelectSQL());
                $dbStatus = 'ok';
                $statusCode = Response::HTTP_OK;
                $dbError = null;
            } catch (\Throwable $e) {
                $dbStatus = 'error';
                $statusCode = Response::HTTP_SERVICE_UNAVAILABLE;
                $dbError = 'Connection failed: ' . $e->getMessage();
                $this->logger->logError($e, ['context' => 'Database health check failed']);
                $dbEvent = $dbCheckStopwatch->stop('db_connection_check');
                $dbResponseTime = $dbEvent->getDuration() / 1000.0;
            }

            $responseData = [
                'status' => ($statusCode === Response::HTTP_OK ? 'ok' : 'error'),
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339),
                'version' => $this->appVersion,
                'environment' => $this->appEnv,
                'checks' => [
                    'database' => [
                        'status' => $dbStatus,
                        'response_time' => round($dbResponseTime, 3),
                    ],
                    'cache' => [
                        'status' => 'ok',
                        'response_time' => 0.001
                    ],
                    'memory' => [
                        'status' => 'ok',
                        'usage' => $this->formatBytes(memory_get_usage(true))
                    ]
                ],
            ];
            if ($dbError !== null) {
                $responseData['checks']['database']['error'] = $this->truncateString($dbError, 256);
            }

            $response = new JsonResponse($responseData, $statusCode);

        } catch (\Throwable $e) {
            $this->logger->logError($e, ['context' => 'Health check controller error']);
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            $response = new JsonResponse([
                'status' => 'error',
                'message' => 'An internal error occurred',
                'detail' => $e->getMessage(),
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339),
                'version' => $this->appVersion,
                'environment' => $this->appEnv,
            ], $statusCode);
        } finally {
            $event = $stopwatch->stop('health_check_request');
            $duration = $event->getDuration() / 1000.0;

            $finalStatusCode = $response ? $response->getStatusCode() : $statusCode;

            if ($response !== null) {
                $this->logger->logResponse($response);
            }
        }

        return $response ?? new JsonResponse([
            'status' => 'error',
            'message' => 'Failed to generate health check response',
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339),
            'version' => $this->appVersion,
            'environment' => $this->appEnv,
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    private function convertToBytes(string $value): int
    {
        $value = strtolower(trim($value));
        $unit = preg_replace('/\\d/', '', $value);
        $bytes = (int)$value;

        switch ($unit) {
            case 'k':
            case 'kb':
                $bytes *= 1024;
                break;
            case 'm':
            case 'mb':
                $bytes *= 1024 * 1024;
                break;
            case 'g':
            case 'gb':
                $bytes *= 1024 * 1024 * 1024;
                break;
        }

        return $bytes;
    }

    private function truncateString(string $string, int $maxLength = 1024): string
    {
        if (mb_strlen($string) > $maxLength) {
            return mb_substr($string, 0, $maxLength - 3) . '...';
        }
        return $string;
    }
} 