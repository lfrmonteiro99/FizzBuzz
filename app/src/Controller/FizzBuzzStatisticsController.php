<?php

namespace App\Controller;

/**
 * @codeCoverageIgnore
 */

use App\Interface\FizzBuzzStatisticsServiceInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Stopwatch\Stopwatch;
use App\Interface\RequestLoggerInterface;
use Symfony\Component\HttpFoundation\Request;

#[OA\Tag(name: 'Statistics')]
class FizzBuzzStatisticsController extends AbstractController
{
    public function __construct(
        private readonly FizzBuzzStatisticsServiceInterface $statisticsService,
        private readonly RequestLoggerInterface $logger,
    ) {
    }

    #[Route('/fizzbuzz/statistics', name: 'get_statistics', methods: ['GET'])]
    #[OA\Get(
        path: '/fizzbuzz/statistics',
        summary: 'Get most frequent FizzBuzz request',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Statistics retrieved successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            nullable: true,
                            example: 'No FizzBuzz requests have been made yet.'
                        ),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            nullable: true,
                            properties: [
                                new OA\Property(
                                    property: 'most_frequent_request',
                                    type: 'object',
                                    nullable: true,
                                    properties: [
                                        new OA\Property(property: 'parameters', type: 'object'),
                                        new OA\Property(property: 'hits', type: 'integer')
                                    ]
                                )
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function getStatistics(Request $request): JsonResponse
    {
        $stopwatch = new Stopwatch(true);
        $stopwatch->start('statistics_request');
        $path = $request->getPathInfo();
        $response = null;
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

        try {
            $this->logger->logRequest($request);

            $mostFrequentRequest = $this->statisticsService->getMostFrequentRequest();

            if ($mostFrequentRequest === null) {
                $response = new JsonResponse([
                    'status' => 'success',
                    'message' => 'No FizzBuzz requests have been made yet.',
                    'data' => null
                ], Response::HTTP_OK);
            } else {
                $response = new JsonResponse([
                    'status' => 'success',
                    'data' => [
                        'most_frequent_request' => $mostFrequentRequest
                    ]
                ], Response::HTTP_OK);
            }
            $statusCode = $response->getStatusCode();

        } catch (\Throwable $e) {
            $this->logger->logError($e, ['context' => 'Statistics controller error']);
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            $response = new JsonResponse([
                'status' => 'error',
                'message' => 'An internal error occurred',
                'detail' => $e->getMessage(),
            ], $statusCode);
        } finally {
            $event = $stopwatch->stop('statistics_request');
            $duration = $event->getDuration() / 1000.0;

            if ($response !== null) {
                $this->logger->logResponse($response);
            }
        }

        return $response ?? new JsonResponse(['error' => 'An unexpected error occurred'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
} 