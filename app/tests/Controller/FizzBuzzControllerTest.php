<?php

namespace App\Tests\Controller;

use App\Controller\FizzBuzzController;
use App\Interface\FizzBuzzRequestFactoryInterface;
use App\Interface\FizzBuzzRequestInterface;
use App\Interface\FizzBuzzResponseFactoryInterface;
use App\Interface\FizzBuzzServiceInterface;
use App\Interface\RequestLoggerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FizzBuzzControllerTest extends TestCase
{
    private FizzBuzzServiceInterface $fizzBuzzService;
    private RequestLoggerInterface $logger;
    private FizzBuzzRequestFactoryInterface $requestFactory;
    private FizzBuzzResponseFactoryInterface $responseFactory;
    private FizzBuzzController $controller;

    protected function setUp(): void
    {
        $this->fizzBuzzService = $this->createMock(FizzBuzzServiceInterface::class);
        $this->logger = $this->createMock(RequestLoggerInterface::class);
        $this->requestFactory = $this->createMock(FizzBuzzRequestFactoryInterface::class);
        $this->responseFactory = $this->createMock(FizzBuzzResponseFactoryInterface::class);
        
        $this->controller = new FizzBuzzController(
            $this->fizzBuzzService,
            $this->logger,
            $this->requestFactory,
            $this->responseFactory
        );
    }

    public function testFizzBuzzWithValidParameters(): void
    {
        $request = new Request([
            'divisor1' => '3',
            'divisor2' => '5',
            'limit' => '15',
            'str1' => 'Fizz',
            'str2' => 'Buzz'
        ]);

        // Set up mock expectations for the logger
        $this->logger->expects($this->once())
            ->method('logRequest')
            ->with($request);
        
        $this->logger->expects($this->once())
            ->method('logResponse')
            ->with($this->isInstanceOf(JsonResponse::class));

        // Mock the request factory to return a DTO
        $fizzBuzzRequestDto = $this->createMock(FizzBuzzRequestInterface::class);
        $this->requestFactory->expects($this->once())
            ->method('createFromRequest')
            ->with($request)
            ->willReturn($fizzBuzzRequestDto);

        // Mock the service to return the expected sequence
        $expectedSequence = ['1', '2', 'Fizz', '4', 'Buzz', 'Fizz', '7', '8', 'Fizz', 'Buzz', '11', 'Fizz', '13', '14', 'FizzBuzz'];
        $this->fizzBuzzService->expects($this->once())
            ->method('generateSequence')
            ->with($fizzBuzzRequestDto)
            ->willReturn($expectedSequence);

        // Mock the response factory
        $expectedResponse = new JsonResponse(['status' => 'success', 'data' => $expectedSequence]);
        $this->responseFactory->expects($this->once())
            ->method('createResponse')
            ->with($expectedSequence)
            ->willReturn($expectedResponse);

        // Call the controller method
        $response = $this->controller->fizzBuzz($request);

        // Assert the response
        $this->assertSame($expectedResponse, $response);
    }

    public function testFizzBuzzWithInvalidParameters(): void
    {
        $request = new Request([
            'divisor1' => '0',
            'divisor2' => '5',
            'limit' => '15',
            'str1' => 'Fizz',
            'str2' => 'Buzz'
        ]);

        // Set up logger expectations
        $this->logger->expects($this->once())
            ->method('logRequest')
            ->with($request);

        $this->logger->expects($this->once())
            ->method('logResponse')
            ->with($this->isInstanceOf(JsonResponse::class));

        // Mock request factory to throw an exception
        $exception = new \InvalidArgumentException(json_encode(['details' => ['divisor1: Value must be greater than 0']]));
        $this->requestFactory->expects($this->once())
            ->method('createFromRequest')
            ->with($request)
            ->willThrowException($exception);

        // Mock response factory for error response
        $errorDetails = ['divisor1: Value must be greater than 0'];
        $expectedErrorResponse = new JsonResponse(['status' => 'error', 'errors' => $errorDetails], Response::HTTP_BAD_REQUEST);
        $this->responseFactory->expects($this->once())
            ->method('createErrorResponse')
            ->with($errorDetails, Response::HTTP_BAD_REQUEST)
            ->willReturn($expectedErrorResponse);

        // Call the controller method
        $response = $this->controller->fizzBuzz($request);

        // Assert the response
        $this->assertSame($expectedErrorResponse, $response);
    }
} 