<?php

namespace App\Controller;

use App\Entity\FizzBuzzRequest;
use App\Dto\FizzBuzzRequestDTO;
use App\Interface\FizzBuzzServiceInterface;
use App\Service\FizzBuzzStatisticsService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FizzBuzzController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $this->logger->info('Home endpoint accessed');
        return new JsonResponse(['status' => 'API is working']);
    }

    #[Route('/fizzbuzz', name: 'app_fizzbuzz', methods: ['GET'])]
    public function getFizzBuzz(
        Request $request,
        FizzBuzzServiceInterface $fizzBuzzService,
        ValidatorInterface $validator,
        FizzBuzzStatisticsService $statisticsService
    ): JsonResponse {
        $this->logger->info('FizzBuzz endpoint accessed', ['query_params' => $request->query->all()]);

        try {
            $params = $request->query->all();
            
            // Create and validate the DTO
            $fizzBuzzRequestDTO = new FizzBuzzRequestDTO();
            $fizzBuzzRequestDTO->limit = (int)($params['limit'] ?? 0);
            $fizzBuzzRequestDTO->int1 = (int)($params['int1'] ?? 0);
            $fizzBuzzRequestDTO->int2 = (int)($params['int2'] ?? 0);
            $fizzBuzzRequestDTO->str1 = (string)($params['str1'] ?? '');
            $fizzBuzzRequestDTO->str2 = (string)($params['str2'] ?? '');
            
            $this->logger->info('DTO created', ['dto' => (array)$fizzBuzzRequestDTO]);
            
            $violations = $validator->validate($fizzBuzzRequestDTO);
            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
                }
                $this->logger->error('Validation errors', ['errors' => $errors]);
                return new JsonResponse(['errors' => $errors], 400);
            }

            // Create FizzBuzzRequest entity using the constructor
            $fizzBuzzRequest = new FizzBuzzRequest(
                $fizzBuzzRequestDTO->limit,
                $fizzBuzzRequestDTO->int1,
                $fizzBuzzRequestDTO->int2,
                $fizzBuzzRequestDTO->str1,
                $fizzBuzzRequestDTO->str2
            );

            // Track the request in statistics
            $statisticsService->trackRequest(
                $fizzBuzzRequest->getLimit(),
                $fizzBuzzRequest->getInt1(),
                $fizzBuzzRequest->getInt2(),
                $fizzBuzzRequest->getStr1(),
                $fizzBuzzRequest->getStr2()
            );

            $this->logger->info('Generating FizzBuzz sequence');
            
            // Generate the sequence
            $result = $fizzBuzzService->generate($fizzBuzzRequest);
            
            $this->logger->info('FizzBuzz sequence generated', ['count' => count($result)]);

            return new JsonResponse([
                'result' => $result,
                'request' => [
                    'limit' => $fizzBuzzRequest->getLimit(),
                    'int1' => $fizzBuzzRequest->getInt1(),
                    'int2' => $fizzBuzzRequest->getInt2(),
                    'str1' => $fizzBuzzRequest->getStr1(),
                    'str2' => $fizzBuzzRequest->getStr2(),
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error in FizzBuzzController', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return new JsonResponse([
                'error' => 'An error occurred while processing your request',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
