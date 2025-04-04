<?php

namespace App\Controller;

use App\Entity\FizzBuzzRequest;
use App\Dto\FizzBuzzRequestDTO;
use App\Interface\FizzBuzzServiceInterface;
use App\Service\FizzBuzzStatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FizzBuzzController extends AbstractController
{
    #[Route('/fizzbuzz', name: 'app_fizzbuzz', methods: ['GET'])]
    public function getFizzBuzz(
        Request $request,
        FizzBuzzServiceInterface $fizzBuzzService,
        ValidatorInterface $validator,
        FizzBuzzStatisticsService $statisticsService
    ): JsonResponse {
        $params = $request->query->all();
        
        // Create and validate the DTO
        $fizzBuzzRequestDTO = new FizzBuzzRequestDTO();
        $fizzBuzzRequestDTO->limit = (int)($params['limit'] ?? 0);
        $fizzBuzzRequestDTO->int1 = (int)($params['int1'] ?? 0);
        $fizzBuzzRequestDTO->int2 = (int)($params['int2'] ?? 0);
        $fizzBuzzRequestDTO->str1 = (string)($params['str1'] ?? '');
        $fizzBuzzRequestDTO->str2 = (string)($params['str2'] ?? '');
        
        $violations = $validator->validate($fizzBuzzRequestDTO);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
            }
            return new JsonResponse(['errors' => $errors], 400);
        }
        
        // Create Entity from DTO for persistence
        $fizzBuzzRequest = FizzBuzzRequest::fromArray([
            'limit' => $fizzBuzzRequestDTO->limit,
            'int1' => $fizzBuzzRequestDTO->int1,
            'int2' => $fizzBuzzRequestDTO->int2,
            'str1' => $fizzBuzzRequestDTO->str1,
            'str2' => $fizzBuzzRequestDTO->str2
        ]);
        
        // Track the request in statistics
        $statisticsService->trackRequest(
            $fizzBuzzRequest->getLimit(),
            $fizzBuzzRequest->getInt1(),
            $fizzBuzzRequest->getInt2(),
            $fizzBuzzRequest->getStr1(),
            $fizzBuzzRequest->getStr2()
        );

        $result = $fizzBuzzService->generate($fizzBuzzRequest);
        return new JsonResponse($result);
    }
}
