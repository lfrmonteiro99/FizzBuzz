<?php

namespace App\Factory;

use App\Dto\FizzBuzzRequestDto;
use App\Interface\FizzBuzzRequestFactoryInterface;
use App\Interface\FizzBuzzRequestInterface;
use App\Interface\FizzBuzzRequestValidatorInterface;
use App\Request\FizzBuzzRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FizzBuzzRequestFactory implements FizzBuzzRequestFactoryInterface
{
    public function __construct(
        private readonly FizzBuzzRequestValidatorInterface $validator,
        private readonly ValidatorInterface $symfonyValidator
    ) {
    }

    public function createFromRequest(Request $request): FizzBuzzRequestInterface
    {
        $dto = FizzBuzzRequestDto::fromRequest($request->query->all());
        
        // Validate DTO
        $violations = $this->symfonyValidator->validate($dto);
        if (count($violations) > 0) {
            throw new \InvalidArgumentException((string) $violations);
        }
        
        $fizzBuzzRequest = new FizzBuzzRequest(
            $dto->getStart(),
            $dto->getLimit(),
            $dto->getDivisor1(),
            $dto->getDivisor2(),
            $dto->getStr1(),
            $dto->getStr2()
        );

        // Validate domain object
        $this->validator->validate($fizzBuzzRequest);
        
        return $fizzBuzzRequest;
    }
} 