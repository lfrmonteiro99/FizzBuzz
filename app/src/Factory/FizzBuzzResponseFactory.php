<?php

namespace App\Factory;

use App\Interface\FizzBuzzResponseFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class FizzBuzzResponseFactory implements FizzBuzzResponseFactoryInterface
{
    public function createResponse(array $sequence): JsonResponse
    {
        return new JsonResponse([
            'sequence' => $sequence,
            'count' => count($sequence)
        ]);
    }
    
    public function createErrorResponse(array $errors, int $statusCode): JsonResponse
    {
        return new JsonResponse([
            'error' => 'An error occurred',
            'details' => $errors
        ], $statusCode);
    }
} 