<?php

namespace App\Controller;

/**
 * @codeCoverageIgnore
 */

use App\Factory\FizzBuzzRequestFactory;
use App\Interface\FizzBuzzRequestFactoryInterface;
use App\Interface\FizzBuzzResponseFactoryInterface;
use App\Interface\FizzBuzzServiceInterface;
use App\Interface\RequestLoggerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\HttpFoundation\Response;

#[OA\Tag(name: 'FizzBuzz')]
class FizzBuzzController extends AbstractController
{
    public function __construct(
        private readonly FizzBuzzServiceInterface $fizzBuzzService,
        private readonly RequestLoggerInterface $logger,
        private readonly FizzBuzzRequestFactoryInterface $requestFactory,
        private readonly FizzBuzzResponseFactoryInterface $responseFactory,
    ) {
    }

    #[Route('/fizzbuzz', name: 'fizzbuzz', methods: ['GET'])]
    #[OA\Get(
        path: '/fizzbuzz',
        summary: 'Generate a FizzBuzz sequence',
        description: 'Generates a sequence of numbers where multiples of divisor1 are replaced with str1, multiples of divisor2 are replaced with str2, and multiples of both are replaced with str1str2.',
        parameters: [
            new OA\Parameter(
                name: 'divisor1',
                description: 'First divisor',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100)
            ),
            new OA\Parameter(
                name: 'divisor2',
                description: 'Second divisor',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100)
            ),
            new OA\Parameter(
                name: 'limit',
                description: 'Upper limit for the sequence',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 1000)
            ),
            new OA\Parameter(
                name: 'str1',
                description: 'String to replace multiples of divisor1',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', minLength: 1, maxLength: 10)
            ),
            new OA\Parameter(
                name: 'str2',
                description: 'String to replace multiples of divisor2',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', minLength: 1, maxLength: 10)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sequence generated successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'string'))
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid parameters',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'message', type: 'string', example: 'Invalid parameters'),
                        new OA\Property(property: 'details', type: 'array', items: new OA\Items(type: 'string'))
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Internal server error',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'message', type: 'string', example: 'An unexpected error occurred')
                    ]
                )
            )
        ]
    )]
    public function fizzBuzz(Request $request): JsonResponse
    {
        $stopwatch = new Stopwatch(true);
        $stopwatch->start('fizzbuzz_request');
        $path = $request->getPathInfo(); // Or use route name 'fizzbuzz'
        $response = null;
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR; // Default to error

        try {
            // Logger for request start (assuming logger has this method)
            $this->logger->logRequest($request);
            
            // Create DTO from request parameters
            $fizzBuzzRequestDto = $this->requestFactory->createFromRequest($request);
            
            // Generate sequence using the service
            $result = $this->fizzBuzzService->generateSequence($fizzBuzzRequestDto);
            
            // Create success response
            $response = $this->responseFactory->createResponse($result);
            $statusCode = $response->getStatusCode();

        } catch (\InvalidArgumentException $e) {
            // Handle validation errors specifically
            $statusCode = Response::HTTP_BAD_REQUEST;
            $response = $this->responseFactory->createErrorResponse(json_decode($e->getMessage(), true)['details'] ?? [$e->getMessage()], $statusCode);

        } catch (\Throwable $e) {
            dd($e->getMessage());
            // Handle generic errors
            $this->logger->logError($e, ['request' => $request->query->all()]); // Log the error
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            $response = $this->responseFactory->createErrorResponse(['An unexpected error occurred'], $statusCode);

        } finally {
            $event = $stopwatch->stop('fizzbuzz_request');
            $duration = $event->getDuration() / 1000.0; // Duration in seconds

            // Log response (assuming logger handles this)
            if ($response !== null) {
                $this->logger->logResponse($response);
            }
        }

        // Ensure a response is always returned
        return $response ?? $this->responseFactory->createErrorResponse(['An unexpected error occurred'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
