<?php

namespace App\Interface;

use App\Interface\FizzBuzzRequestInterface;
use Symfony\Component\HttpFoundation\Request;

interface FizzBuzzRequestFactoryInterface
{
    /**
     * Create a FizzBuzzRequest from an HTTP request.
     *
     * @param Request $request The HTTP request
     * @return FizzBuzzRequestInterface The created FizzBuzz request
     */
    public function createFromRequest(Request $request): FizzBuzzRequestInterface;
} 