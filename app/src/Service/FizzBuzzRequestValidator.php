<?php

namespace App\Service;

use App\Interface\FizzBuzzRequestInterface;
use App\Interface\FizzBuzzRequestValidatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FizzBuzzRequestValidator implements FizzBuzzRequestValidatorInterface
{
    private const MAX_DIVISOR = 100;
    private const MAX_LIMIT = 1000;
    
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function validate(FizzBuzzRequestInterface $request): void
    {
        $errors = [];
        
        if ($request->getStart() <= 0) {
            $errors[] = ['field' => 'start', 'message' => 'The start value must be a positive number.'];
        }
        
        if ($request->getDivisor1() <= 0) {
            $errors[] = ['field' => 'divisor1', 'message' => 'The first divisor must be a positive number.'];
        }
        
        if ($request->getDivisor2() <= 0) {
            $errors[] = ['field' => 'divisor2', 'message' => 'The second divisor must be a positive number.'];
        }
        
        if ($request->getDivisor1() > self::MAX_DIVISOR) {
            $errors[] = ['field' => 'divisor1', 'message' => 'The first divisor must not exceed ' . self::MAX_DIVISOR . '.'];
        }
        
        if ($request->getDivisor2() > self::MAX_DIVISOR) {
            $errors[] = ['field' => 'divisor2', 'message' => 'The second divisor must not exceed ' . self::MAX_DIVISOR . '.'];
        }
        
        if ($request->getDivisor1() === $request->getDivisor2()) {
            $errors[] = ['field' => 'divisor2', 'message' => 'The divisors must be different.'];
        }
        
        if ($request->getLimit() <= 0) {
            $errors[] = ['field' => 'limit', 'message' => 'The limit must be a positive number.'];
        }
        
        if ($request->getLimit() > self::MAX_LIMIT) {
            $errors[] = ['field' => 'limit', 'message' => 'The limit must not exceed ' . self::MAX_LIMIT . '.'];
        }
        
        if ($request->getStart() > $request->getLimit()) {
            $errors[] = ['field' => 'start', 'message' => 'The start value must not exceed the limit.'];
        }
        
        if (empty($request->getStr1())) {
            $errors[] = ['field' => 'str1', 'message' => 'The first string cannot be empty.'];
        }
        
        if (empty($request->getStr2())) {
            $errors[] = ['field' => 'str2', 'message' => 'The second string cannot be empty.'];
        }
        
        if (!empty($errors)) {
            $this->logger->info('Validation failed', [
                'start' => $request->getStart(),
                'limit' => $request->getLimit(),
                'divisor1' => $request->getDivisor1(),
                'divisor2' => $request->getDivisor2(),
                'str1' => $request->getStr1(),
                'str2' => $request->getStr2(),
                'errors' => $errors
            ]);
            
            $this->emitValidationFailedEvent($request, $errors);
            
            throw new \InvalidArgumentException(json_encode([
                'error' => 'Invalid parameters',
                'details' => $errors
            ]));
        }
    }
    
    private function emitValidationFailedEvent(FizzBuzzRequestInterface $request, array $errors): void
    {
        $this->eventDispatcher->dispatch(
            new \App\Event\ValidationFailedEvent($request, $errors),
            \App\Event\ValidationFailedEvent::EVENT_NAME
        );
    }
} 