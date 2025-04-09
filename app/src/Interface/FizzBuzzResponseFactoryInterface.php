<?php

namespace App\Interface;

use Symfony\Component\HttpFoundation\JsonResponse;

interface FizzBuzzResponseFactoryInterface
{
    /**
     * Create a JSON response for a FizzBuzz sequence.
     *
     * @param array $sequence The FizzBuzz sequence
     * @return JsonResponse The JSON response
     */
    public function createResponse(array $sequence): JsonResponse;
    
    /**
     * Create an error response.
     *
     * @param array $errors The error messages
     * @param int $statusCode The HTTP status code
     * @return JsonResponse The JSON response
     */
    public function createErrorResponse(array $errors, int $statusCode): JsonResponse;
} 