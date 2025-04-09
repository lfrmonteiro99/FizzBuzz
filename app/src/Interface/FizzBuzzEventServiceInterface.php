<?php

namespace App\Interface;

interface FizzBuzzEventServiceInterface
{
    /**
     * Dispatch a FizzBuzz event.
     *
     * @param FizzBuzzRequestInterface $request The FizzBuzz request
     * @param array $sequence The generated sequence
     */
    public function dispatchEvent(FizzBuzzRequestInterface $request, array $sequence): void;
} 